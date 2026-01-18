import { onMounted, onUnmounted } from 'vue';

export interface UseAnalysisKeyboardOptions {
    onGoBack: () => void;
    onGoForward: () => void;
    onGoStart: () => void;
    onGoEnd: () => void;
    onPrevVariation: () => void;
    onNextVariation: () => void;
    onDelete: () => void;
    onPass?: () => void;
    onPromote?: () => void;
    onShowHelp?: () => void;
    onToggleFocus?: () => void;
}

/**
 * Composable for handling keyboard shortcuts in analysis mode
 *
 * Shortcuts:
 * - Left Arrow: Previous move
 * - Right Arrow: Next move
 * - Home: Go to start
 * - End: Go to end of line
 * - Up Arrow: Previous variation
 * - Down Arrow: Next variation
 * - P: Pass
 * - Ctrl+Delete: Delete current branch
 * - Ctrl+P: Promote variation (optional)
 * - ?: Show keyboard shortcuts help
 */
export function useAnalysisKeyboard(options: UseAnalysisKeyboardOptions) {
    function handleKeyDown(event: KeyboardEvent) {
        // Ignore if user is typing in an input or textarea
        const target = event.target as HTMLElement;
        if (
            target.tagName === 'INPUT' ||
            target.tagName === 'TEXTAREA' ||
            target.isContentEditable
        ) {
            return;
        }

        switch (event.key) {
            case 'ArrowLeft':
                event.preventDefault();
                options.onGoBack();
                break;

            case 'ArrowRight':
                event.preventDefault();
                options.onGoForward();
                break;

            case 'ArrowUp':
                event.preventDefault();
                options.onPrevVariation();
                break;

            case 'ArrowDown':
                event.preventDefault();
                options.onNextVariation();
                break;

            case 'Home':
                event.preventDefault();
                options.onGoStart();
                break;

            case 'End':
                event.preventDefault();
                options.onGoEnd();
                break;

            case 'Delete':
            case 'Backspace':
                if (event.ctrlKey || event.metaKey) {
                    event.preventDefault();
                    options.onDelete();
                }
                break;

            case 'p':
            case 'P':
                if (event.ctrlKey || event.metaKey) {
                    // Ctrl+P: Promote variation
                    if (options.onPromote) {
                        event.preventDefault();
                        options.onPromote();
                    }
                } else if (options.onPass) {
                    // P without modifier: Pass
                    event.preventDefault();
                    options.onPass();
                }
                break;

            case '?':
                if (options.onShowHelp) {
                    event.preventDefault();
                    options.onShowHelp();
                }
                break;

            case 'f':
            case 'F':
                if (!event.ctrlKey && !event.metaKey && options.onToggleFocus) {
                    event.preventDefault();
                    options.onToggleFocus();
                }
                break;
        }
    }

    onMounted(() => {
        window.addEventListener('keydown', handleKeyDown);
    });

    onUnmounted(() => {
        window.removeEventListener('keydown', handleKeyDown);
    });
}
