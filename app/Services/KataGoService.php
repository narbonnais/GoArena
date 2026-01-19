<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;
use RuntimeException;

class KataGoService
{
    private $process = null;

    private $pipes = [];

    /**
     * Start the KataGo process with proper parameters
     */
    public function start(): void
    {
        if ($this->process !== null) {
            return;
        }

        $binary = config('katago.binary');
        $model = config('katago.model');
        $configFile = config('katago.config');

        if (! file_exists($binary)) {
            throw new RuntimeException("KataGo binary not found at: {$binary}");
        }

        if (! file_exists($model)) {
            throw new RuntimeException("KataGo model not found at: {$model}");
        }

        $command = sprintf(
            '%s gtp -model %s%s',
            escapeshellarg($binary),
            escapeshellarg($model),
            $configFile && file_exists($configFile) ? ' -config '.escapeshellarg($configFile) : ''
        );

        $descriptors = [
            0 => ['pipe', 'r'], // stdin
            1 => ['pipe', 'w'], // stdout
            2 => ['pipe', 'w'], // stderr
        ];

        $env = [
            'PATH' => '/usr/local/bin:/usr/bin:/bin',
            'HOME' => '/tmp',
            'USER' => 'www-data',
        ];

        $this->process = proc_open($command, $descriptors, $this->pipes, null, $env);

        if (! is_resource($this->process)) {
            // Clean up any pipes that may have been created
            foreach ($this->pipes as $pipe) {
                if (is_resource($pipe)) {
                    fclose($pipe);
                }
            }
            $this->pipes = [];
            throw new RuntimeException('Failed to start KataGo process');
        }

        // Set pipes to non-blocking mode
        stream_set_blocking($this->pipes[1], false);
        stream_set_blocking($this->pipes[2], false);

        // Wait for KataGo to initialize
        usleep(3000000); // 3 seconds - Human SL model needs more time

        // Read any initial output to clear buffers
        $this->readOutput();
    }

    /**
     * Send a GTP command to KataGo and get the response
     */
    public function sendCommand(string $command): string
    {
        if ($this->process === null) {
            $this->start();
        }

        // Send command
        fwrite($this->pipes[0], $command."\n");
        fflush($this->pipes[0]);

        // Wait for response with timeout
        $timeout = config('katago.timeout', 30);
        $startTime = time();
        $response = '';

        while (true) {
            $line = $this->readLine();

            if ($line !== null) {
                $response .= $line."\n";

                // GTP responses end with an empty line
                if (trim($line) === '') {
                    break;
                }
            }

            if (time() - $startTime > $timeout) {
                throw new RuntimeException("KataGo command timed out: {$command}");
            }

            usleep(10000); // 10ms
        }

        return trim($response);
    }

    /**
     * Read a line from KataGo output
     */
    private function readLine(): ?string
    {
        $line = fgets($this->pipes[1]);

        return $line !== false ? rtrim($line, "\r\n") : null;
    }

    /**
     * Read all available output from KataGo
     */
    private function readOutput(): string
    {
        $output = '';
        while (($data = fread($this->pipes[1], 8192)) !== false && $data !== '') {
            $output .= $data;
        }

        return $output;
    }

    /**
     * Get a move from KataGo for the given game state
     */
    public function getMove(array $board, string $color, int $size): ?array
    {
        $profile = config('katago.game');

        return $this->generateMove($board, $color, $size, $profile);
    }

    /**
     * Generate a move with the given profile settings.
     */
    private function generateMove(array $board, string $color, int $size, ?array $profile): ?array
    {
        // Setup board
        $this->sendCommand("boardsize {$size}");
        $this->sendCommand('clear_board');

        // Apply profile if provided
        if ($profile) {
            $this->applyProfile($profile);
        }

        // Replay all moves to reconstruct game state
        $this->replayBoard($board, $size);

        // Get move from KataGo
        $gtpColor = $color === 'black' ? 'B' : 'W';
        $response = $this->sendCommand("genmove {$gtpColor}");

        return $this->parseMoveResponse($response, $size);
    }

    /**
     * Parse a genmove response from KataGo.
     */
    private function parseMoveResponse(string $response, int $size): ?array
    {
        // Parse response (format: "= D4" or "= pass" or "= resign")
        if (preg_match('/^=\s*(\S+)/m', $response, $matches)) {
            $move = strtolower($matches[1]);

            if ($move === 'pass' || $move === 'resign') {
                return null;
            }

            return $this->parseVertex($move, $size);
        }

        // Error response starts with "?"
        if (str_starts_with(trim($response), '?')) {
            throw new RuntimeException("KataGo error: {$response}");
        }

        return null;
    }

    /**
     * Replay the board state by placing stones
     */
    private function replayBoard(array $board, int $size): void
    {
        for ($y = 0; $y < $size; $y++) {
            for ($x = 0; $x < $size; $x++) {
                $stone = $board[$y][$x] ?? null;
                if ($stone !== null) {
                    $vertex = $this->coordToVertex($x, $y, $size);
                    $gtpColor = $stone === 'black' ? 'B' : 'W';
                    $this->sendCommand("play {$gtpColor} {$vertex}");
                }
            }
        }
    }

    /**
     * Convert board coordinates to GTP vertex (e.g., "D4")
     */
    private function coordToVertex(int $x, int $y, int $size): string
    {
        // GTP uses letters A-T (skipping I) for columns, 1-19 for rows (bottom to top)
        $letters = 'ABCDEFGHJKLMNOPQRST'; // Note: no I
        $col = $letters[$x] ?? 'A';
        $row = $size - $y; // GTP rows are 1-indexed from bottom

        return $col.$row;
    }

    /**
     * Parse a GTP vertex (e.g., "D4") to board coordinates
     */
    private function parseVertex(string $vertex, int $size): ?array
    {
        $vertex = strtoupper($vertex);

        if (! preg_match('/^([A-HJ-T])(\d+)$/', $vertex, $matches)) {
            return null;
        }

        $letters = 'ABCDEFGHJKLMNOPQRST';
        $x = strpos($letters, $matches[1]);
        $row = (int) $matches[2];
        $y = $size - $row;

        if ($x === false || $x >= $size || $y < 0 || $y >= $size) {
            return null;
        }

        return ['x' => $x, 'y' => $y];
    }

    /**
     * Analyze the current position and return evaluation data
     */
    public function analyze(array $board, string $color, int $size, array $settings = []): array
    {
        // Apply defaults from config with strict type casting to prevent command injection
        $limits = config('katago.analysis.limits');
        $maxVisits = (int) ($settings['maxVisits'] ?? $limits['maxVisits']['default'] ?? 100);
        $numSearchThreads = (int) ($settings['numSearchThreads'] ?? $limits['numSearchThreads']['default'] ?? 1);
        $multiPV = (int) ($settings['multiPV'] ?? $limits['multiPV']['default'] ?? 5);

        // Setup board
        $this->sendCommand(sprintf('boardsize %d', $size));
        $this->sendCommand('clear_board');

        // Apply the analysis profile (full strength, no humanSL restriction)
        $analysisProfile = config('katago.analysis');
        $this->applyProfile($analysisProfile);

        // Apply user-configured settings with explicit integer formatting
        $this->sendCommand(sprintf('kata-set-param maxVisits %d', $maxVisits));
        $this->sendCommand(sprintf('kata-set-param numSearchThreads %d', $numSearchThreads));
        $this->sendCommand(sprintf('kata-set-param multiPV %d', $multiPV));

        // Replay all moves to reconstruct game state
        $this->replayBoard($board, $size);

        // Use kata-analyze to get evaluation
        // Format: kata-analyze <color> <maxMoves>
        $gtpColor = $color === 'black' ? 'B' : 'W';

        // Send kata-analyze command with interval (in centiseconds)
        // We want a single analysis result, so we use a short interval
        $response = $this->sendAnalyzeCommand($gtpColor);

        // Log raw response for debugging
        Log::debug('KataGo raw response', ['response' => $response, 'settings' => $settings]);

        return $this->parseAnalysisResponse($response, $size, $color, $multiPV);
    }

    /**
     * Get the best move without level restriction
     */
    public function getBestMove(array $board, string $color, int $size): ?array
    {
        $analysisProfile = config('katago.analysis');

        return $this->generateMove($board, $color, $size, $analysisProfile);
    }

    /**
     * Send kata-analyze command and get response
     */
    private function sendAnalyzeCommand(string $color): string
    {
        if ($this->process === null) {
            $this->start();
        }

        // Temporarily set stdout to blocking for reliable reading
        stream_set_blocking($this->pipes[1], true);

        // kata-analyze returns info about the position
        // We use "kata-analyze <color> <interval>" where interval is in centiseconds
        $command = "kata-analyze {$color} 50";

        fwrite($this->pipes[0], $command."\n");
        fflush($this->pipes[0]);

        // Set a read timeout
        stream_set_timeout($this->pipes[1], 2);

        // Read responses until we get enough info lines
        $response = '';
        $infoCount = 0;
        $startTime = microtime(true);
        $maxTime = 2.5; // Max 2.5 seconds

        while (microtime(true) - $startTime < $maxTime) {
            $line = fgets($this->pipes[1]);

            if ($line === false) {
                // Check if we timed out or got EOF
                $meta = stream_get_meta_data($this->pipes[1]);
                if ($meta['timed_out'] || $meta['eof']) {
                    break;
                }

                continue;
            }

            $line = rtrim($line, "\r\n");
            $response .= $line."\n";

            // Count info lines - we want at least a few moves analyzed
            if (str_starts_with($line, 'info')) {
                $infoCount++;
                // Once we have enough info, we can stop
                if ($infoCount >= 20) {
                    break;
                }
            }
        }

        // Stop the analysis by sending newline
        fwrite($this->pipes[0], "\n");
        fflush($this->pipes[0]);

        // Set back to non-blocking
        stream_set_blocking($this->pipes[1], false);

        // Clear any remaining output
        usleep(50000);
        while (fgets($this->pipes[1]) !== false) {
            // discard
        }

        return $response;
    }

    /**
     * Parse KataGo analysis response
     */
    private function parseAnalysisResponse(string $response, int $size, string $currentPlayer, int $maxMoves = 5): array
    {
        $result = [
            'winRate' => [
                'black' => 50.0,
                'white' => 50.0,
            ],
            'scoreEstimate' => [
                'lead' => 0.0,
                'winner' => 'black',
            ],
            'topMoves' => [],
        ];

        // Track moves by coordinate to deduplicate (keep highest visits)
        $movesByCoord = [];
        // Track the best order=0 move for overall evaluation
        $bestOrder0Visits = 0;

        // Parse info lines
        // Format: info move D4 visits 100 winrate 0.5432 scoreMean 2.5 ...
        $lines = explode("\n", $response);

        foreach ($lines as $line) {
            if (! str_starts_with($line, 'info')) {
                continue;
            }

            // Extract move information using regex
            // Pattern: move <vertex> ... winrate <float> ... scoreMean <float>
            if (preg_match('/move\s+(\S+)/', $line, $moveMatch)) {
                $vertex = $moveMatch[1];
                $coord = $this->parseVertex(strtolower($vertex), $size);

                if ($coord === null && strtolower($vertex) !== 'pass') {
                    continue;
                }

                $winrate = 0.5;
                $scoreMean = 0.0;
                $visits = 0;
                $order = 0;

                if (preg_match('/winrate\s+([\d.]+)/', $line, $wrMatch)) {
                    $winrate = (float) $wrMatch[1];
                }

                if (preg_match('/scoreMean\s+([-\d.]+)/', $line, $smMatch)) {
                    $scoreMean = (float) $smMatch[1];
                }

                if (preg_match('/visits\s+(\d+)/', $line, $vMatch)) {
                    $visits = (int) $vMatch[1];
                }

                if (preg_match('/order\s+(\d+)/', $line, $oMatch)) {
                    $order = (int) $oMatch[1];
                }

                // The first move (order 0) gives us the overall position evaluation
                // Use the one with most visits for most refined analysis
                if ($order === 0 && $visits > $bestOrder0Visits) {
                    $bestOrder0Visits = $visits;

                    // Winrate is from perspective of player to move
                    if ($currentPlayer === 'black') {
                        $result['winRate']['black'] = round($winrate * 100, 1);
                        $result['winRate']['white'] = round((1 - $winrate) * 100, 1);
                    } else {
                        $result['winRate']['white'] = round($winrate * 100, 1);
                        $result['winRate']['black'] = round((1 - $winrate) * 100, 1);
                    }

                    // Score mean is from perspective of player to move
                    if ($currentPlayer === 'black') {
                        $result['scoreEstimate']['lead'] = round($scoreMean, 1);
                        $result['scoreEstimate']['winner'] = $scoreMean >= 0 ? 'black' : 'white';
                    } else {
                        $result['scoreEstimate']['lead'] = round(-$scoreMean, 1);
                        $result['scoreEstimate']['winner'] = $scoreMean >= 0 ? 'white' : 'black';
                    }
                }

                // Deduplicate moves by coordinate, keeping the one with most visits
                if ($coord !== null) {
                    $coordKey = "{$coord['x']},{$coord['y']}";

                    if (! isset($movesByCoord[$coordKey]) || $visits > $movesByCoord[$coordKey]['visits']) {
                        $movesByCoord[$coordKey] = [
                            'coordinate' => $coord,
                            'winRate' => round($winrate * 100, 1),
                            'rank' => $order + 1,
                            'visits' => $visits,
                        ];
                    }
                }
            }
        }

        // Convert deduplicated moves to array
        $result['topMoves'] = array_values($movesByCoord);

        // Sort top moves by visits (most visits = best analysis)
        usort($result['topMoves'], fn ($a, $b) => $b['visits'] - $a['visits']);

        // Re-assign ranks based on sorted order
        foreach ($result['topMoves'] as $i => &$move) {
            $move['rank'] = $i + 1;
        }
        unset($move);

        // Limit to requested number of moves
        $result['topMoves'] = array_slice($result['topMoves'], 0, $maxMoves);

        return $result;
    }

    /**
     * Apply a KataGo profile (humanSLProfile and maxVisits)
     */
    private function applyProfile(array $profile): void
    {
        // Set humanSLProfile (only if explicitly set, not null)
        // Note: Using empty string can cause errors with some KataGo models,
        // so for full-strength analysis we simply skip setting humanSLProfile
        // Validate humanSLProfile is alphanumeric to prevent command injection
        if (isset($profile['humanSLProfile']) && $profile['humanSLProfile'] !== null) {
            $humanSLProfile = preg_replace('/[^a-zA-Z0-9_-]/', '', (string) $profile['humanSLProfile']);
            if (! empty($humanSLProfile)) {
                $this->sendCommand(sprintf('kata-set-param humanSLProfile %s', $humanSLProfile));
            }
        }

        // Set maxVisits with strict integer formatting
        if (isset($profile['maxVisits'])) {
            $this->sendCommand(sprintf('kata-set-param maxVisits %d', (int) $profile['maxVisits']));
        }
    }

    /**
     * Stop the KataGo process
     */
    public function stop(): void
    {
        if ($this->process === null) {
            return;
        }

        // Send quit command
        try {
            fwrite($this->pipes[0], "quit\n");
            fflush($this->pipes[0]);
        } catch (\Exception $e) {
            Log::warning('KataGo quit command failed', ['error' => $e->getMessage()]);
        }

        // Close pipes
        foreach ($this->pipes as $pipe) {
            if (is_resource($pipe)) {
                fclose($pipe);
            }
        }

        // Close process
        proc_close($this->process);

        $this->process = null;
        $this->pipes = [];
    }

    public function __destruct()
    {
        $this->stop();
    }
}
