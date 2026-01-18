// ═══════════════════════════════════════════════════════════════
// ISHI — Go Playing Platform
// Main Application
// ═══════════════════════════════════════════════════════════════

const { useState, useEffect, useRef, createContext, useContext } = React;

// ═══════════════════════════════════════════════════════════════
// Context & State Management
// ═══════════════════════════════════════════════════════════════

const AppContext = createContext();

const useApp = () => useContext(AppContext);

// ═══════════════════════════════════════════════════════════════
// Icons (SVG Components)
// ═══════════════════════════════════════════════════════════════

const Icons = {
  Play: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polygon points="5 3 19 12 5 21 5 3"/>
    </svg>
  ),
  User: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/>
      <circle cx="12" cy="7" r="4"/>
    </svg>
  ),
  Users: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/>
      <circle cx="9" cy="7" r="4"/>
      <path d="M23 21v-2a4 4 0 0 0-3-3.87"/>
      <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
    </svg>
  ),
  Book: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M4 19.5A2.5 2.5 0 0 1 6.5 17H20"/>
      <path d="M6.5 2H20v20H6.5A2.5 2.5 0 0 1 4 19.5v-15A2.5 2.5 0 0 1 6.5 2z"/>
    </svg>
  ),
  Trophy: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M6 9H4.5a2.5 2.5 0 0 1 0-5H6"/>
      <path d="M18 9h1.5a2.5 2.5 0 0 0 0-5H18"/>
      <path d="M4 22h16"/>
      <path d="M10 14.66V17c0 .55-.47.98-.97 1.21C7.85 18.75 7 20.24 7 22"/>
      <path d="M14 14.66V17c0 .55.47.98.97 1.21C16.15 18.75 17 20.24 17 22"/>
      <path d="M18 2H6v7a6 6 0 0 0 12 0V2Z"/>
    </svg>
  ),
  Clock: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="10"/>
      <polyline points="12 6 12 12 16 14"/>
    </svg>
  ),
  Settings: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="3"/>
      <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/>
    </svg>
  ),
  ChevronRight: () => (
    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polyline points="9 18 15 12 9 6"/>
    </svg>
  ),
  ArrowRight: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <line x1="5" y1="12" x2="19" y2="12"/>
      <polyline points="12 5 19 12 12 19"/>
    </svg>
  ),
  Globe: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="10"/>
      <line x1="2" y1="12" x2="22" y2="12"/>
      <path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/>
    </svg>
  ),
  Zap: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"/>
    </svg>
  ),
  Target: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <circle cx="12" cy="12" r="10"/>
      <circle cx="12" cy="12" r="6"/>
      <circle cx="12" cy="12" r="2"/>
    </svg>
  ),
  Menu: () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <line x1="3" y1="12" x2="21" y2="12"/>
      <line x1="3" y1="6" x2="21" y2="6"/>
      <line x1="3" y1="18" x2="21" y2="18"/>
    </svg>
  ),
  X: () => (
    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <line x1="18" y1="6" x2="6" y2="18"/>
      <line x1="6" y1="6" x2="18" y2="18"/>
    </svg>
  ),
  History: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M3 3v5h5"/>
      <path d="M3.05 13A9 9 0 1 0 6 5.3L3 8"/>
      <path d="M12 7v5l4 2"/>
    </svg>
  ),
  Puzzle: () => (
    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round">
      <path d="M19.439 7.85c-.049.322.059.648.289.878l1.568 1.568c.47.47.706 1.087.706 1.704s-.235 1.233-.706 1.704l-1.611 1.611a.98.98 0 0 1-.837.276c-.47-.07-.802-.452-.968-.904a2.5 2.5 0 1 0-4.086 2.5c.296.408.276.956-.052 1.328l-1.611 1.611c-.47.47-1.087.706-1.704.706s-1.233-.235-1.704-.706l-1.568-1.568a1.027 1.027 0 0 0-.878-.29c-.322.049-.648-.059-.878-.289L4.28 16.21c-.47-.47-.706-1.087-.706-1.704s.235-1.233.706-1.704l1.611-1.611a.98.98 0 0 1 .837-.276c.47.07.802.452.968.904a2.5 2.5 0 1 0 4.086-2.5c-.296-.408-.276-.956.052-1.328l1.611-1.611c.47-.47 1.087-.706 1.704-.706s1.233.235 1.704.706l1.568 1.568c.23.23.556.338.878.29z"/>
    </svg>
  ),
};

// ═══════════════════════════════════════════════════════════════
// Mini Board Preview Component
// ═══════════════════════════════════════════════════════════════

const MiniBoardPreview = ({ size = 9, stones = [], className = '' }) => {
  const cellSize = 100 / (size + 1);

  return (
    <div className={`mini-board ${className}`} style={{
      width: '100%',
      aspectRatio: '1',
      background: 'linear-gradient(145deg, #e8d4a8 0%, #c9a962 100%)',
      borderRadius: '8px',
      position: 'relative',
      boxShadow: 'inset 0 1px 0 rgba(255,255,255,0.3), 0 4px 12px rgba(139, 115, 85, 0.2)',
    }}>
      <svg width="100%" height="100%" viewBox="0 0 100 100" style={{ position: 'absolute', inset: 0 }}>
        {/* Grid lines */}
        {Array.from({ length: size }).map((_, i) => (
          <React.Fragment key={i}>
            <line
              x1={cellSize}
              y1={cellSize * (i + 1)}
              x2={100 - cellSize}
              y2={cellSize * (i + 1)}
              stroke="#8b7355"
              strokeWidth="0.3"
            />
            <line
              x1={cellSize * (i + 1)}
              y1={cellSize}
              x2={cellSize * (i + 1)}
              y2={100 - cellSize}
              stroke="#8b7355"
              strokeWidth="0.3"
            />
          </React.Fragment>
        ))}
        {/* Star points for 9x9 */}
        {size === 9 && [
          [3, 3], [3, 7], [7, 3], [7, 7], [5, 5]
        ].map(([x, y], i) => (
          <circle
            key={i}
            cx={cellSize * x}
            cy={cellSize * y}
            r="1.2"
            fill="#8b7355"
          />
        ))}
        {/* Stones */}
        {stones.map((stone, i) => (
          <circle
            key={i}
            cx={cellSize * (stone.x + 1)}
            cy={cellSize * (stone.y + 1)}
            r={cellSize * 0.42}
            fill={stone.color === 'black' ? '#1a1a1a' : '#f5f2ed'}
            style={{
              filter: stone.color === 'black'
                ? 'drop-shadow(0 1px 2px rgba(0,0,0,0.3))'
                : 'drop-shadow(0 1px 2px rgba(0,0,0,0.15))'
            }}
          />
        ))}
      </svg>
    </div>
  );
};

// ═══════════════════════════════════════════════════════════════
// Navigation Component
// ═══════════════════════════════════════════════════════════════

const Navigation = ({ currentPage, setCurrentPage, user, setShowAuth }) => {
  const [scrolled, setScrolled] = useState(false);
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  useEffect(() => {
    const handleScroll = () => setScrolled(window.scrollY > 20);
    window.addEventListener('scroll', handleScroll);
    return () => window.removeEventListener('scroll', handleScroll);
  }, []);

  const navLinks = [
    { id: 'play', label: 'Play' },
    { id: 'learn', label: 'Learn' },
    { id: 'puzzles', label: 'Puzzles' },
    { id: 'community', label: 'Community' },
  ];

  return (
    <nav className={`nav ${scrolled ? 'scrolled' : ''}`}>
      <a
        href="#"
        className="nav-logo"
        onClick={(e) => { e.preventDefault(); setCurrentPage('landing'); }}
      >
        <div className="nav-logo-mark" />
        <span>Ishi</span>
      </a>

      <div className="nav-links">
        {navLinks.map(link => (
          <a
            key={link.id}
            href="#"
            className={`nav-link ${currentPage === link.id ? 'active' : ''}`}
            onClick={(e) => { e.preventDefault(); setCurrentPage(link.id); }}
          >
            {link.label}
          </a>
        ))}
      </div>

      <div className="nav-actions">
        {user ? (
          <>
            <button
              className="btn btn-primary"
              onClick={() => setCurrentPage('play')}
            >
              <Icons.Play /> Play Now
            </button>
            <button
              className="btn btn-icon btn-ghost"
              onClick={() => setCurrentPage('profile')}
              style={{ marginLeft: '4px' }}
            >
              <Icons.User />
            </button>
          </>
        ) : (
          <>
            <button
              className="btn btn-ghost"
              onClick={() => setShowAuth('login')}
            >
              Log in
            </button>
            <button
              className="btn btn-primary"
              onClick={() => setShowAuth('register')}
            >
              Sign up
            </button>
          </>
        )}
      </div>
    </nav>
  );
};

// ═══════════════════════════════════════════════════════════════
// Landing Page
// ═══════════════════════════════════════════════════════════════

const LandingPage = ({ setCurrentPage, setShowAuth }) => {
  const heroStones = [
    { x: 3, y: 3, color: 'black' },
    { x: 4, y: 3, color: 'white' },
    { x: 3, y: 4, color: 'white' },
    { x: 5, y: 4, color: 'black' },
    { x: 4, y: 5, color: 'black' },
    { x: 5, y: 5, color: 'white' },
    { x: 6, y: 4, color: 'black' },
    { x: 4, y: 4, color: 'black' },
  ];

  return (
    <div className="landing">
      {/* Hero Section */}
      <section className="hero">
        <div className="container">
          <div className="hero-content">
            <div className="hero-text">
              <p className="text-label animate-fade-in-up stagger-1">
                The ancient game, reimagined
              </p>
              <h1 className="display-xl animate-fade-in-up stagger-2">
                Play Go<br />with the world
              </h1>
              <p className="hero-subtitle text-lg text-muted animate-fade-in-up stagger-3">
                Join millions of players in the world's oldest strategy game.
                One click to play. No complicated settings. Just pure Go.
              </p>
              <div className="hero-actions animate-fade-in-up stagger-4">
                <button
                  className="btn btn-primary btn-lg"
                  onClick={() => setCurrentPage('play')}
                >
                  <Icons.Play /> Play Now — It's Free
                </button>
                <button
                  className="btn btn-secondary btn-lg"
                  onClick={() => setCurrentPage('learn')}
                >
                  Learn Go
                </button>
              </div>
              <div className="hero-stats animate-fade-in-up stagger-5">
                <div className="hero-stat">
                  <span className="hero-stat-value">2.4M</span>
                  <span className="hero-stat-label">Players</span>
                </div>
                <div className="hero-stat-divider" />
                <div className="hero-stat">
                  <span className="hero-stat-value">847K</span>
                  <span className="hero-stat-label">Games Today</span>
                </div>
                <div className="hero-stat-divider" />
                <div className="hero-stat">
                  <span className="hero-stat-value">12K</span>
                  <span className="hero-stat-label">Online Now</span>
                </div>
              </div>
            </div>
            <div className="hero-visual animate-scale-in stagger-3">
              <div className="hero-board-wrapper">
                <MiniBoardPreview size={9} stones={heroStones} />
                <div className="hero-board-glow" />
              </div>
            </div>
          </div>
        </div>
        <div className="hero-bg" />
      </section>

      {/* Features Section */}
      <section className="features">
        <div className="container">
          <div className="features-header">
            <p className="text-label">Why Ishi</p>
            <h2 className="display-md">Go, simplified</h2>
            <p className="text-muted" style={{ maxWidth: '500px', margin: '0 auto' }}>
              We've removed the complexity of traditional Go platforms.
              Standard rules, instant matching, beautiful play.
            </p>
          </div>

          <div className="features-grid">
            <div className="feature-card">
              <div className="feature-icon">
                <Icons.Zap />
              </div>
              <h3>One-Click Play</h3>
              <p className="text-muted text-sm">
                No rule debates. No komi negotiations. Click play, get matched, start your game in seconds.
              </p>
            </div>

            <div className="feature-card">
              <div className="feature-icon">
                <Icons.Globe />
              </div>
              <h3>Global Community</h3>
              <p className="text-muted text-sm">
                Play with opponents from Korea, Japan, China, and 150+ countries. Unified rankings, one world.
              </p>
            </div>

            <div className="feature-card">
              <div className="feature-icon">
                <Icons.Target />
              </div>
              <h3>All Skill Levels</h3>
              <p className="text-muted text-sm">
                From your first game to professional-level play. Our matchmaking finds your perfect opponent.
              </p>
            </div>

            <div className="feature-card">
              <div className="feature-icon">
                <Icons.Book />
              </div>
              <h3>Learn as You Play</h3>
              <p className="text-muted text-sm">
                Interactive tutorials, puzzles, and AI analysis help you improve with every game.
              </p>
            </div>
          </div>
        </div>
      </section>

      {/* Board Sizes Section */}
      <section className="board-sizes">
        <div className="container">
          <div className="board-sizes-header">
            <p className="text-label">Choose Your Board</p>
            <h2 className="display-md">Three sizes, infinite possibilities</h2>
          </div>

          <div className="board-sizes-grid">
            <div className="board-size-card" onClick={() => setCurrentPage('play')}>
              <div className="board-size-preview">
                <MiniBoardPreview size={9} stones={[
                  { x: 4, y: 4, color: 'black' },
                  { x: 5, y: 5, color: 'white' },
                ]} />
              </div>
              <div className="board-size-info">
                <h3>9×9</h3>
                <p className="text-sm text-muted">Quick games, perfect for beginners and lunch breaks</p>
                <span className="board-size-time">~10 min</span>
              </div>
            </div>

            <div className="board-size-card featured" onClick={() => setCurrentPage('play')}>
              <div className="board-size-badge">Most Popular</div>
              <div className="board-size-preview">
                <MiniBoardPreview size={13} stones={[
                  { x: 6, y: 6, color: 'black' },
                  { x: 7, y: 7, color: 'white' },
                  { x: 7, y: 6, color: 'black' },
                ]} />
              </div>
              <div className="board-size-info">
                <h3>13×13</h3>
                <p className="text-sm text-muted">The sweet spot — deep strategy, reasonable time</p>
                <span className="board-size-time">~25 min</span>
              </div>
            </div>

            <div className="board-size-card" onClick={() => setCurrentPage('play')}>
              <div className="board-size-preview">
                <MiniBoardPreview size={19} stones={[
                  { x: 3, y: 3, color: 'black' },
                  { x: 15, y: 15, color: 'white' },
                  { x: 15, y: 3, color: 'black' },
                  { x: 3, y: 15, color: 'white' },
                ]} />
              </div>
              <div className="board-size-info">
                <h3>19×19</h3>
                <p className="text-sm text-muted">The classic experience — full strategic depth</p>
                <span className="board-size-time">~45 min</span>
              </div>
            </div>
          </div>
        </div>
      </section>

      {/* CTA Section */}
      <section className="cta">
        <div className="container">
          <div className="cta-content">
            <h2 className="display-lg">Ready to play?</h2>
            <p className="text-lg text-muted">
              Join the global Go community today. No credit card required.
            </p>
            <button
              className="btn btn-accent btn-lg"
              onClick={() => setShowAuth('register')}
            >
              Create Free Account <Icons.ArrowRight />
            </button>
          </div>
        </div>
      </section>

      {/* Footer */}
      <footer className="footer">
        <div className="container">
          <div className="footer-content">
            <div className="footer-brand">
              <div className="nav-logo">
                <div className="nav-logo-mark" />
                <span>Ishi</span>
              </div>
              <p className="text-sm text-muted">
                Play Go online with players from around the world.
              </p>
            </div>
            <div className="footer-links">
              <div className="footer-column">
                <h4 className="text-label">Play</h4>
                <a href="#">Quick Match</a>
                <a href="#">Tournaments</a>
                <a href="#">vs Computer</a>
              </div>
              <div className="footer-column">
                <h4 className="text-label">Learn</h4>
                <a href="#">Tutorials</a>
                <a href="#">Puzzles</a>
                <a href="#">Strategy</a>
              </div>
              <div className="footer-column">
                <h4 className="text-label">Community</h4>
                <a href="#">Forums</a>
                <a href="#">Clubs</a>
                <a href="#">Events</a>
              </div>
            </div>
          </div>
          <div className="footer-bottom">
            <p className="text-sm text-muted">© 2025 Ishi. The way of Go.</p>
          </div>
        </div>
      </footer>

      <style>{`
        .landing {
          overflow-x: hidden;
        }

        /* Hero */
        .hero {
          min-height: 100vh;
          display: flex;
          align-items: center;
          padding: 120px 0 80px;
          position: relative;
        }

        .hero-bg {
          position: absolute;
          inset: 0;
          background:
            radial-gradient(ellipse at 70% 20%, rgba(232, 212, 168, 0.3) 0%, transparent 50%),
            radial-gradient(ellipse at 30% 80%, rgba(193, 127, 89, 0.1) 0%, transparent 40%);
          pointer-events: none;
        }

        .hero-content {
          display: grid;
          grid-template-columns: 1fr 1fr;
          gap: 80px;
          align-items: center;
          position: relative;
          z-index: 1;
        }

        .hero-text {
          max-width: 560px;
        }

        .hero-subtitle {
          margin: 24px 0 32px;
          line-height: 1.7;
        }

        .hero-actions {
          display: flex;
          gap: 16px;
          margin-bottom: 48px;
        }

        .hero-stats {
          display: flex;
          align-items: center;
          gap: 24px;
        }

        .hero-stat {
          display: flex;
          flex-direction: column;
        }

        .hero-stat-value {
          font-family: var(--font-display);
          font-size: 1.75rem;
          font-weight: 500;
          color: var(--text-primary);
        }

        .hero-stat-label {
          font-size: 0.75rem;
          color: var(--text-muted);
          text-transform: uppercase;
          letter-spacing: 0.05em;
        }

        .hero-stat-divider {
          width: 1px;
          height: 40px;
          background: var(--bg-tertiary);
        }

        .hero-visual {
          display: flex;
          justify-content: center;
        }

        .hero-board-wrapper {
          width: 100%;
          max-width: 400px;
          position: relative;
        }

        .hero-board-glow {
          position: absolute;
          inset: -20%;
          background: radial-gradient(circle, rgba(232, 212, 168, 0.4) 0%, transparent 70%);
          z-index: -1;
          filter: blur(40px);
        }

        /* Features */
        .features {
          padding: 120px 0;
          background: var(--bg-secondary);
        }

        .features-header {
          text-align: center;
          margin-bottom: 64px;
        }

        .features-header h2 {
          margin: 12px 0 16px;
        }

        .features-grid {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 24px;
        }

        .feature-card {
          background: white;
          padding: 32px 24px;
          border-radius: var(--radius-lg);
          border: 1px solid var(--bg-tertiary);
          transition: all var(--duration-normal) var(--ease-out);
        }

        .feature-card:hover {
          transform: translateY(-4px);
          box-shadow: var(--shadow-lg);
          border-color: var(--text-muted);
        }

        .feature-icon {
          width: 48px;
          height: 48px;
          display: flex;
          align-items: center;
          justify-content: center;
          background: var(--accent-subtle);
          color: var(--accent);
          border-radius: var(--radius-md);
          margin-bottom: 20px;
        }

        .feature-card h3 {
          font-family: var(--font-body);
          font-size: 1rem;
          font-weight: 600;
          margin-bottom: 8px;
        }

        /* Board Sizes */
        .board-sizes {
          padding: 120px 0;
        }

        .board-sizes-header {
          text-align: center;
          margin-bottom: 64px;
        }

        .board-sizes-header h2 {
          margin-top: 12px;
        }

        .board-sizes-grid {
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 32px;
          max-width: 1000px;
          margin: 0 auto;
        }

        .board-size-card {
          background: white;
          border-radius: var(--radius-lg);
          border: 1px solid var(--bg-tertiary);
          padding: 24px;
          cursor: pointer;
          transition: all var(--duration-normal) var(--ease-out);
          position: relative;
        }

        .board-size-card:hover {
          transform: translateY(-4px);
          box-shadow: var(--shadow-lg);
          border-color: var(--accent);
        }

        .board-size-card.featured {
          border-color: var(--accent);
          box-shadow: 0 0 0 1px var(--accent);
        }

        .board-size-badge {
          position: absolute;
          top: -10px;
          left: 50%;
          transform: translateX(-50%);
          background: var(--accent);
          color: white;
          font-size: 0.6875rem;
          font-weight: 600;
          padding: 4px 12px;
          border-radius: var(--radius-full);
          text-transform: uppercase;
          letter-spacing: 0.05em;
        }

        .board-size-preview {
          margin-bottom: 20px;
        }

        .board-size-info h3 {
          font-family: var(--font-display);
          font-size: 1.5rem;
          margin-bottom: 4px;
        }

        .board-size-time {
          display: inline-block;
          margin-top: 12px;
          font-size: 0.75rem;
          color: var(--accent);
          background: var(--accent-subtle);
          padding: 4px 10px;
          border-radius: var(--radius-full);
          font-weight: 500;
        }

        /* CTA */
        .cta {
          padding: 120px 0;
          background: var(--stone-black);
          color: var(--stone-white);
        }

        .cta-content {
          text-align: center;
          max-width: 600px;
          margin: 0 auto;
        }

        .cta-content h2 {
          color: var(--stone-white);
        }

        .cta-content p {
          margin: 16px 0 32px;
          color: rgba(245, 242, 237, 0.7);
        }

        /* Footer */
        .footer {
          padding: 80px 0 40px;
          background: var(--bg-secondary);
        }

        .footer-content {
          display: grid;
          grid-template-columns: 1fr 2fr;
          gap: 80px;
          margin-bottom: 60px;
        }

        .footer-brand {
          max-width: 280px;
        }

        .footer-brand p {
          margin-top: 16px;
        }

        .footer-links {
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 40px;
        }

        .footer-column h4 {
          margin-bottom: 16px;
        }

        .footer-column a {
          display: block;
          color: var(--text-secondary);
          text-decoration: none;
          font-size: 0.875rem;
          padding: 6px 0;
          transition: color var(--duration-fast);
        }

        .footer-column a:hover {
          color: var(--text-primary);
        }

        .footer-bottom {
          padding-top: 40px;
          border-top: 1px solid var(--bg-tertiary);
        }

        /* Responsive */
        @media (max-width: 1024px) {
          .hero-content {
            grid-template-columns: 1fr;
            text-align: center;
            gap: 60px;
          }

          .hero-text {
            max-width: 100%;
          }

          .hero-actions {
            justify-content: center;
          }

          .hero-stats {
            justify-content: center;
          }

          .hero-board-wrapper {
            max-width: 300px;
          }

          .features-grid {
            grid-template-columns: repeat(2, 1fr);
          }

          .board-sizes-grid {
            grid-template-columns: 1fr;
            max-width: 400px;
          }
        }

        @media (max-width: 640px) {
          .hero {
            padding: 100px 0 60px;
          }

          .hero-actions {
            flex-direction: column;
          }

          .hero-actions .btn {
            width: 100%;
          }

          .hero-stats {
            flex-direction: column;
            gap: 16px;
          }

          .hero-stat-divider {
            width: 40px;
            height: 1px;
          }

          .features-grid {
            grid-template-columns: 1fr;
          }

          .footer-content {
            grid-template-columns: 1fr;
            gap: 40px;
          }

          .footer-links {
            grid-template-columns: 1fr;
            gap: 24px;
          }
        }
      `}</style>
    </div>
  );
};

// ═══════════════════════════════════════════════════════════════
// Play Page / Lobby
// ═══════════════════════════════════════════════════════════════

const PlayPage = ({ setCurrentPage, startGame }) => {
  const [selectedBoard, setSelectedBoard] = useState(9);
  const [selectedTime, setSelectedTime] = useState('blitz');
  const [isSearching, setIsSearching] = useState(false);

  const boardSizes = [
    { size: 9, label: '9×9', desc: 'Quick', time: '~10 min' },
    { size: 13, label: '13×13', desc: 'Medium', time: '~25 min' },
    { size: 19, label: '19×19', desc: 'Classic', time: '~45 min' },
  ];

  const timeControls = [
    { id: 'bullet', label: 'Bullet', main: '3', increment: '+2s', desc: '3 min + 2s/move' },
    { id: 'blitz', label: 'Blitz', main: '5', increment: '+5s', desc: '5 min + 5s/move' },
    { id: 'rapid', label: 'Rapid', main: '15', increment: '+10s', desc: '15 min + 10s/move' },
    { id: 'classical', label: 'Classical', main: '30', increment: '+30s', desc: '30 min + 30s/move' },
  ];

  const handleQuickPlay = () => {
    setIsSearching(true);
    setTimeout(() => {
      setIsSearching(false);
      startGame({ boardSize: selectedBoard, timeControl: selectedTime });
    }, 2000);
  };

  return (
    <div className="play-page">
      <div className="play-container">
        {/* Main Play Section */}
        <div className="play-main">
          <div className="play-header">
            <h1 className="display-md">Play Go</h1>
            <p className="text-muted">Choose your board and time, then find a match</p>
          </div>

          {/* Board Size Selection */}
          <div className="play-section">
            <h3 className="text-label">Board Size</h3>
            <div className="board-selector">
              {boardSizes.map(board => (
                <button
                  key={board.size}
                  className={`board-option ${selectedBoard === board.size ? 'selected' : ''}`}
                  onClick={() => setSelectedBoard(board.size)}
                >
                  <div className="board-option-preview">
                    <MiniBoardPreview size={board.size} stones={[]} />
                  </div>
                  <div className="board-option-info">
                    <span className="board-option-label">{board.label}</span>
                    <span className="board-option-time">{board.time}</span>
                  </div>
                </button>
              ))}
            </div>
          </div>

          {/* Time Control Selection */}
          <div className="play-section">
            <h3 className="text-label">Time Control</h3>
            <div className="time-selector">
              {timeControls.map(time => (
                <button
                  key={time.id}
                  className={`time-option ${selectedTime === time.id ? 'selected' : ''}`}
                  onClick={() => setSelectedTime(time.id)}
                >
                  <div className="time-option-main">
                    <span className="time-value">{time.main}</span>
                    <span className="time-increment">{time.increment}</span>
                  </div>
                  <span className="time-label">{time.label}</span>
                </button>
              ))}
            </div>
          </div>

          {/* Quick Play Button */}
          <button
            className={`quick-play-btn ${isSearching ? 'searching' : ''}`}
            onClick={handleQuickPlay}
            disabled={isSearching}
          >
            {isSearching ? (
              <>
                <div className="search-spinner" />
                <span>Finding opponent...</span>
              </>
            ) : (
              <>
                <Icons.Play />
                <span>Play Now</span>
              </>
            )}
          </button>

          {/* Other Play Options */}
          <div className="play-alternatives">
            <button className="alt-play-btn">
              <Icons.User />
              <div className="alt-play-info">
                <span className="alt-play-title">Play vs Computer</span>
                <span className="alt-play-desc">Practice against AI</span>
              </div>
              <Icons.ChevronRight />
            </button>
            <button className="alt-play-btn">
              <Icons.Users />
              <div className="alt-play-info">
                <span className="alt-play-title">Play a Friend</span>
                <span className="alt-play-desc">Send a challenge link</span>
              </div>
              <Icons.ChevronRight />
            </button>
            <button className="alt-play-btn">
              <Icons.Trophy />
              <div className="alt-play-info">
                <span className="alt-play-title">Tournaments</span>
                <span className="alt-play-desc">Compete for prizes</span>
              </div>
              <Icons.ChevronRight />
            </button>
          </div>
        </div>

        {/* Sidebar */}
        <aside className="play-sidebar">
          {/* Online Players */}
          <div className="sidebar-card">
            <div className="sidebar-card-header">
              <h3 className="text-sm">Online Now</h3>
              <span className="online-indicator">
                <span className="pulse-dot" />
                12,847
              </span>
            </div>
            <div className="online-breakdown">
              <div className="online-item">
                <span>9×9</span>
                <span className="text-muted">3,241</span>
              </div>
              <div className="online-item">
                <span>13×13</span>
                <span className="text-muted">5,892</span>
              </div>
              <div className="online-item">
                <span>19×19</span>
                <span className="text-muted">3,714</span>
              </div>
            </div>
          </div>

          {/* Recent Games */}
          <div className="sidebar-card">
            <div className="sidebar-card-header">
              <h3 className="text-sm">Your Recent Games</h3>
              <a href="#" className="text-sm" style={{ color: 'var(--accent)' }}>View All</a>
            </div>
            <div className="recent-games">
              <div className="recent-game">
                <div className="recent-game-result win">W</div>
                <div className="recent-game-info">
                  <span className="recent-game-opponent">vs Player123</span>
                  <span className="recent-game-details text-xs text-muted">9×9 · +4.5 · 2h ago</span>
                </div>
              </div>
              <div className="recent-game">
                <div className="recent-game-result loss">L</div>
                <div className="recent-game-info">
                  <span className="recent-game-opponent">vs GoMaster</span>
                  <span className="recent-game-details text-xs text-muted">13×13 · R+3.5 · 5h ago</span>
                </div>
              </div>
              <div className="recent-game">
                <div className="recent-game-result win">W</div>
                <div className="recent-game-info">
                  <span className="recent-game-opponent">vs StoneCold</span>
                  <span className="recent-game-details text-xs text-muted">9×9 · +12.5 · 1d ago</span>
                </div>
              </div>
            </div>
          </div>

          {/* Daily Puzzle */}
          <div className="sidebar-card puzzle-card" onClick={() => setCurrentPage('puzzles')}>
            <div className="sidebar-card-header">
              <h3 className="text-sm">Daily Puzzle</h3>
              <span className="puzzle-difficulty">Intermediate</span>
            </div>
            <div className="puzzle-preview">
              <MiniBoardPreview
                size={9}
                stones={[
                  { x: 2, y: 2, color: 'black' },
                  { x: 3, y: 2, color: 'white' },
                  { x: 2, y: 3, color: 'white' },
                  { x: 3, y: 3, color: 'black' },
                  { x: 4, y: 3, color: 'white' },
                  { x: 3, y: 4, color: 'black' },
                ]}
              />
            </div>
            <p className="puzzle-prompt text-sm">Black to play and capture</p>
          </div>
        </aside>
      </div>

      <style>{`
        .play-page {
          padding: 100px 0 60px;
          min-height: 100vh;
          background: var(--bg-primary);
        }

        .play-container {
          max-width: 1200px;
          margin: 0 auto;
          padding: 0 var(--space-xl);
          display: grid;
          grid-template-columns: 1fr 320px;
          gap: 40px;
        }

        .play-main {
          background: white;
          border-radius: var(--radius-lg);
          border: 1px solid var(--bg-tertiary);
          padding: 40px;
        }

        .play-header {
          margin-bottom: 32px;
        }

        .play-header h1 {
          margin-bottom: 8px;
        }

        .play-section {
          margin-bottom: 32px;
        }

        .play-section h3 {
          margin-bottom: 16px;
        }

        /* Board Selector */
        .board-selector {
          display: grid;
          grid-template-columns: repeat(3, 1fr);
          gap: 16px;
        }

        .board-option {
          background: var(--bg-secondary);
          border: 2px solid transparent;
          border-radius: var(--radius-lg);
          padding: 16px;
          cursor: pointer;
          transition: all var(--duration-normal) var(--ease-out);
          text-align: center;
        }

        .board-option:hover {
          background: var(--bg-tertiary);
        }

        .board-option.selected {
          background: white;
          border-color: var(--stone-black);
          box-shadow: var(--shadow-md);
        }

        .board-option-preview {
          width: 100%;
          max-width: 120px;
          margin: 0 auto 12px;
        }

        .board-option-info {
          display: flex;
          flex-direction: column;
          gap: 2px;
        }

        .board-option-label {
          font-family: var(--font-display);
          font-size: 1.25rem;
        }

        .board-option-time {
          font-size: 0.75rem;
          color: var(--text-muted);
        }

        /* Time Selector */
        .time-selector {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 12px;
        }

        .time-option {
          background: var(--bg-secondary);
          border: 2px solid transparent;
          border-radius: var(--radius-md);
          padding: 16px 12px;
          cursor: pointer;
          transition: all var(--duration-normal) var(--ease-out);
          text-align: center;
        }

        .time-option:hover {
          background: var(--bg-tertiary);
        }

        .time-option.selected {
          background: white;
          border-color: var(--stone-black);
          box-shadow: var(--shadow-md);
        }

        .time-option-main {
          display: flex;
          align-items: baseline;
          justify-content: center;
          gap: 4px;
          margin-bottom: 4px;
        }

        .time-value {
          font-family: var(--font-display);
          font-size: 1.5rem;
          font-weight: 500;
        }

        .time-increment {
          font-size: 0.75rem;
          color: var(--text-muted);
        }

        .time-label {
          font-size: 0.8125rem;
          color: var(--text-secondary);
        }

        /* Quick Play Button */
        .quick-play-btn {
          width: 100%;
          padding: 20px 32px;
          background: var(--stone-black);
          color: white;
          border: none;
          border-radius: var(--radius-lg);
          font-family: var(--font-body);
          font-size: 1.125rem;
          font-weight: 600;
          cursor: pointer;
          display: flex;
          align-items: center;
          justify-content: center;
          gap: 12px;
          transition: all var(--duration-normal) var(--ease-out);
          margin-bottom: 32px;
        }

        .quick-play-btn:hover:not(:disabled) {
          background: #2a2a2a;
          transform: translateY(-2px);
          box-shadow: var(--shadow-lg);
        }

        .quick-play-btn:disabled {
          cursor: default;
        }

        .quick-play-btn.searching {
          background: var(--accent);
        }

        .search-spinner {
          width: 20px;
          height: 20px;
          border: 2px solid rgba(255,255,255,0.3);
          border-top-color: white;
          border-radius: 50%;
          animation: spin 1s linear infinite;
        }

        @keyframes spin {
          to { transform: rotate(360deg); }
        }

        /* Alternative Play Options */
        .play-alternatives {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .alt-play-btn {
          display: flex;
          align-items: center;
          gap: 16px;
          padding: 16px;
          background: var(--bg-secondary);
          border: 1px solid transparent;
          border-radius: var(--radius-md);
          cursor: pointer;
          transition: all var(--duration-fast) var(--ease-out);
          text-align: left;
          width: 100%;
        }

        .alt-play-btn:hover {
          background: var(--bg-tertiary);
          border-color: var(--bg-tertiary);
        }

        .alt-play-btn svg:first-child {
          color: var(--text-muted);
        }

        .alt-play-info {
          flex: 1;
          display: flex;
          flex-direction: column;
        }

        .alt-play-title {
          font-weight: 500;
          font-size: 0.9375rem;
        }

        .alt-play-desc {
          font-size: 0.8125rem;
          color: var(--text-muted);
        }

        .alt-play-btn svg:last-child {
          color: var(--text-muted);
        }

        /* Sidebar */
        .play-sidebar {
          display: flex;
          flex-direction: column;
          gap: 20px;
        }

        .sidebar-card {
          background: white;
          border-radius: var(--radius-lg);
          border: 1px solid var(--bg-tertiary);
          padding: 20px;
        }

        .sidebar-card-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 16px;
        }

        .sidebar-card-header h3 {
          font-weight: 600;
        }

        .online-indicator {
          display: flex;
          align-items: center;
          gap: 6px;
          font-size: 0.875rem;
          font-weight: 600;
          color: var(--success);
        }

        .pulse-dot {
          width: 8px;
          height: 8px;
          background: var(--success);
          border-radius: 50%;
          animation: pulse 2s infinite;
        }

        .online-breakdown {
          display: flex;
          flex-direction: column;
          gap: 8px;
        }

        .online-item {
          display: flex;
          justify-content: space-between;
          font-size: 0.875rem;
        }

        /* Recent Games */
        .recent-games {
          display: flex;
          flex-direction: column;
          gap: 12px;
        }

        .recent-game {
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .recent-game-result {
          width: 28px;
          height: 28px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 0.75rem;
          font-weight: 700;
        }

        .recent-game-result.win {
          background: rgba(90, 143, 90, 0.15);
          color: var(--success);
        }

        .recent-game-result.loss {
          background: rgba(184, 84, 80, 0.15);
          color: var(--error);
        }

        .recent-game-info {
          display: flex;
          flex-direction: column;
        }

        .recent-game-opponent {
          font-size: 0.875rem;
          font-weight: 500;
        }

        /* Puzzle Card */
        .puzzle-card {
          cursor: pointer;
          transition: all var(--duration-normal) var(--ease-out);
        }

        .puzzle-card:hover {
          border-color: var(--accent);
          box-shadow: var(--shadow-md);
        }

        .puzzle-difficulty {
          font-size: 0.6875rem;
          background: var(--accent-subtle);
          color: var(--accent);
          padding: 2px 8px;
          border-radius: var(--radius-full);
          font-weight: 500;
        }

        .puzzle-preview {
          margin-bottom: 12px;
        }

        .puzzle-prompt {
          color: var(--text-secondary);
        }

        /* Responsive */
        @media (max-width: 1024px) {
          .play-container {
            grid-template-columns: 1fr;
          }

          .play-sidebar {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
          }
        }

        @media (max-width: 768px) {
          .board-selector {
            grid-template-columns: 1fr;
            gap: 12px;
          }

          .board-option {
            display: flex;
            align-items: center;
            gap: 16px;
            text-align: left;
          }

          .board-option-preview {
            width: 60px;
            margin: 0;
          }

          .time-selector {
            grid-template-columns: repeat(2, 1fr);
          }

          .play-sidebar {
            grid-template-columns: 1fr;
          }
        }
      `}</style>
    </div>
  );
};

// ═══════════════════════════════════════════════════════════════
// Game Page - Interactive Go Board
// ═══════════════════════════════════════════════════════════════

const GamePage = ({ gameConfig, setCurrentPage }) => {
  const size = gameConfig?.boardSize || 9;
  const [stones, setStones] = useState([]);
  const [currentPlayer, setCurrentPlayer] = useState('black');
  const [blackTime, setBlackTime] = useState(300);
  const [whiteTime, setWhiteTime] = useState(300);
  const [blackCaptures, setBlackCaptures] = useState(0);
  const [whiteCaptures, setWhiteCaptures] = useState(0);
  const [lastMove, setLastMove] = useState(null);
  const [showResignModal, setShowResignModal] = useState(false);

  const cellSize = 100 / (size + 1);

  // Get star points based on board size
  const getStarPoints = () => {
    if (size === 9) return [[3,3],[3,7],[7,3],[7,7],[5,5]];
    if (size === 13) return [[4,4],[4,10],[10,4],[10,10],[7,7],[4,7],[10,7],[7,4],[7,10]];
    if (size === 19) return [[4,4],[4,10],[4,16],[10,4],[10,10],[10,16],[16,4],[16,10],[16,16]];
    return [];
  };

  // Format time as MM:SS
  const formatTime = (seconds) => {
    const m = Math.floor(seconds / 60);
    const s = seconds % 60;
    return `${m}:${s.toString().padStart(2, '0')}`;
  };

  // Handle stone placement
  const handleIntersectionClick = (x, y) => {
    // Only allow player to move on their turn (player is always black)
    if (currentPlayer !== 'black') return;

    // Check if position is already occupied
    if (stones.find(s => s.x === x && s.y === y)) return;

    const newStone = { x, y, color: currentPlayer };
    setStones([...stones, newStone]);
    setLastMove({ x, y });
    setCurrentPlayer(currentPlayer === 'black' ? 'white' : 'black');
  };

  // AI move effect - call API when it's white's turn
  useEffect(() => {
    if (currentPlayer !== 'white') return;

    // Convert stones array to 2D board for API
    const board = Array(size).fill(null).map(() => Array(size).fill(null));
    stones.forEach(s => {
      board[s.y][s.x] = s.color;
    });

    const fetchAIMove = async () => {
      try {
        const response = await fetch('/api/go/ai-move', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.content || '',
          },
          body: JSON.stringify({
            board,
            currentPlayer: 'white',
            boardSize: size,
            level: gameConfig?.aiLevel || 'beginner',
            moveHistory: null,
            koPoint: null,
          }),
        });

        const data = await response.json();

        if (data.move) {
          const { x, y } = data.move;
          setStones(prev => [...prev, { x, y, color: 'white' }]);
          setLastMove({ x, y });
        }
        setCurrentPlayer('black');
      } catch (error) {
        console.error('AI move failed:', error);
        setCurrentPlayer('black');
      }
    };

    const timeout = setTimeout(fetchAIMove, 500);
    return () => clearTimeout(timeout);
  }, [currentPlayer, stones, size, gameConfig?.aiLevel]);

  // Timer effect
  useEffect(() => {
    const timer = setInterval(() => {
      if (currentPlayer === 'black') {
        setBlackTime(t => Math.max(0, t - 1));
      } else {
        setWhiteTime(t => Math.max(0, t - 1));
      }
    }, 1000);
    return () => clearInterval(timer);
  }, [currentPlayer]);

  return (
    <div className="game-page">
      <div className="game-container">
        {/* Left Panel - Black Player */}
        <div className="player-panel black-panel">
          <div className={`player-card ${currentPlayer === 'black' ? 'active' : ''}`}>
            <div className="player-stone stone-black-indicator" />
            <div className="player-info">
              <span className="player-name">You</span>
              <span className="player-rank text-sm text-muted">15 kyu</span>
            </div>
            <div className="player-time">
              <Icons.Clock />
              <span className={blackTime < 60 ? 'time-low' : ''}>{formatTime(blackTime)}</span>
            </div>
          </div>
          <div className="player-stats">
            <div className="stat-item">
              <span className="stat-label">Captures</span>
              <span className="stat-value">{blackCaptures}</span>
            </div>
          </div>
        </div>

        {/* Center - Game Board */}
        <div className="game-board-wrapper">
          <div className="game-board" style={{ aspectRatio: '1' }}>
            <svg width="100%" height="100%" viewBox="0 0 100 100">
              {/* Board background */}
              <defs>
                <linearGradient id="boardGradient" x1="0%" y1="0%" x2="100%" y2="100%">
                  <stop offset="0%" stopColor="#e8d4a8" />
                  <stop offset="100%" stopColor="#c9a962" />
                </linearGradient>
                <filter id="stoneShadow" x="-20%" y="-20%" width="140%" height="140%">
                  <feDropShadow dx="0" dy="0.3" stdDeviation="0.4" floodOpacity="0.3"/>
                </filter>
              </defs>
              <rect x="0" y="0" width="100" height="100" fill="url(#boardGradient)" rx="1"/>

              {/* Grid lines */}
              {Array.from({ length: size }).map((_, i) => (
                <React.Fragment key={i}>
                  <line
                    x1={cellSize}
                    y1={cellSize * (i + 1)}
                    x2={100 - cellSize}
                    y2={cellSize * (i + 1)}
                    stroke="#8b7355"
                    strokeWidth="0.25"
                  />
                  <line
                    x1={cellSize * (i + 1)}
                    y1={cellSize}
                    x2={cellSize * (i + 1)}
                    y2={100 - cellSize}
                    stroke="#8b7355"
                    strokeWidth="0.25"
                  />
                </React.Fragment>
              ))}

              {/* Star points */}
              {getStarPoints().map(([x, y], i) => (
                <circle
                  key={i}
                  cx={cellSize * x}
                  cy={cellSize * y}
                  r={size === 9 ? 0.8 : 0.6}
                  fill="#8b7355"
                />
              ))}

              {/* Click targets */}
              {Array.from({ length: size }).map((_, y) =>
                Array.from({ length: size }).map((_, x) => (
                  <circle
                    key={`${x}-${y}`}
                    cx={cellSize * (x + 1)}
                    cy={cellSize * (y + 1)}
                    r={cellSize * 0.45}
                    fill="transparent"
                    className="intersection"
                    onClick={() => handleIntersectionClick(x, y)}
                  />
                ))
              )}

              {/* Stones */}
              {stones.map((stone, i) => (
                <g key={i} filter="url(#stoneShadow)">
                  <circle
                    cx={cellSize * (stone.x + 1)}
                    cy={cellSize * (stone.y + 1)}
                    r={cellSize * 0.43}
                    fill={stone.color === 'black' ? '#1a1a1a' : '#f5f2ed'}
                    className="stone-placed"
                  />
                  {/* Highlight for last move */}
                  {lastMove && lastMove.x === stone.x && lastMove.y === stone.y && (
                    <circle
                      cx={cellSize * (stone.x + 1)}
                      cy={cellSize * (stone.y + 1)}
                      r={cellSize * 0.15}
                      fill={stone.color === 'black' ? '#f5f2ed' : '#1a1a1a'}
                      opacity="0.6"
                    />
                  )}
                </g>
              ))}
            </svg>
          </div>

          {/* Game Controls */}
          <div className="game-controls">
            <button className="game-control-btn" title="Pass">
              Pass
            </button>
            <button
              className="game-control-btn resign"
              onClick={() => setShowResignModal(true)}
              title="Resign"
            >
              Resign
            </button>
            <button className="game-control-btn" title="Undo Request">
              Undo
            </button>
            <button className="game-control-btn" title="Settings">
              <Icons.Settings />
            </button>
          </div>
        </div>

        {/* Right Panel - White Player */}
        <div className="player-panel white-panel">
          <div className={`player-card ${currentPlayer === 'white' ? 'active' : ''}`}>
            <div className="player-stone stone-white-indicator" />
            <div className="player-info">
              <span className="player-name">Opponent</span>
              <span className="player-rank text-sm text-muted">14 kyu</span>
            </div>
            <div className="player-time">
              <Icons.Clock />
              <span className={whiteTime < 60 ? 'time-low' : ''}>{formatTime(whiteTime)}</span>
            </div>
          </div>
          <div className="player-stats">
            <div className="stat-item">
              <span className="stat-label">Captures</span>
              <span className="stat-value">{whiteCaptures}</span>
            </div>
          </div>

          {/* Move History */}
          <div className="move-history">
            <h4 className="text-label">Moves</h4>
            <div className="moves-list">
              {stones.length === 0 ? (
                <p className="text-sm text-muted">No moves yet</p>
              ) : (
                stones.slice(-10).map((stone, i) => (
                  <div key={i} className="move-item">
                    <span className={`move-color ${stone.color}`} />
                    <span className="text-sm">
                      {String.fromCharCode(65 + stone.x)}{stone.y + 1}
                    </span>
                  </div>
                ))
              )}
            </div>
          </div>
        </div>
      </div>

      {/* Resign Modal */}
      {showResignModal && (
        <div className="modal-overlay" onClick={() => setShowResignModal(false)}>
          <div className="modal" onClick={e => e.stopPropagation()}>
            <h3 className="display-md">Resign Game?</h3>
            <p className="text-muted">This action cannot be undone.</p>
            <div className="modal-actions">
              <button
                className="btn btn-secondary"
                onClick={() => setShowResignModal(false)}
              >
                Cancel
              </button>
              <button
                className="btn btn-primary"
                style={{ background: 'var(--error)' }}
                onClick={() => setCurrentPage('play')}
              >
                Resign
              </button>
            </div>
          </div>
        </div>
      )}

      <style>{`
        .game-page {
          min-height: 100vh;
          background: var(--bg-secondary);
          padding: 80px 0 40px;
        }

        .game-container {
          max-width: 1400px;
          margin: 0 auto;
          padding: 0 var(--space-xl);
          display: grid;
          grid-template-columns: 240px 1fr 240px;
          gap: 32px;
          align-items: start;
        }

        /* Player Panels */
        .player-panel {
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .player-card {
          background: white;
          border-radius: var(--radius-lg);
          border: 2px solid var(--bg-tertiary);
          padding: 20px;
          display: flex;
          flex-direction: column;
          gap: 12px;
          transition: all var(--duration-normal) var(--ease-out);
        }

        .player-card.active {
          border-color: var(--accent);
          box-shadow: 0 0 0 3px var(--accent-subtle);
        }

        .player-stone {
          width: 32px;
          height: 32px;
          border-radius: 50%;
        }

        .stone-black-indicator {
          background: radial-gradient(ellipse at 30% 30%, #4a4a4a 0%, #1a1a1a 50%, #0a0a0a 100%);
          box-shadow: 0 2px 4px rgba(0,0,0,0.3);
        }

        .stone-white-indicator {
          background: radial-gradient(ellipse at 30% 30%, #ffffff 0%, #f5f2ed 50%, #d8d5d0 100%);
          box-shadow: 0 2px 4px rgba(0,0,0,0.15);
        }

        .player-info {
          display: flex;
          flex-direction: column;
        }

        .player-name {
          font-weight: 600;
          font-size: 1rem;
        }

        .player-time {
          display: flex;
          align-items: center;
          gap: 8px;
          font-family: var(--font-display);
          font-size: 1.5rem;
          font-weight: 500;
        }

        .player-time svg {
          width: 18px;
          height: 18px;
          color: var(--text-muted);
        }

        .time-low {
          color: var(--error);
        }

        .player-stats {
          background: white;
          border-radius: var(--radius-md);
          border: 1px solid var(--bg-tertiary);
          padding: 16px;
        }

        .stat-item {
          display: flex;
          justify-content: space-between;
        }

        .stat-label {
          font-size: 0.875rem;
          color: var(--text-muted);
        }

        .stat-value {
          font-weight: 600;
        }

        /* Game Board */
        .game-board-wrapper {
          display: flex;
          flex-direction: column;
          gap: 20px;
        }

        .game-board {
          background: linear-gradient(145deg, #e8d4a8 0%, #c9a962 100%);
          border-radius: var(--radius-lg);
          box-shadow:
            inset 0 1px 0 rgba(255,255,255,0.3),
            0 8px 32px rgba(139, 115, 85, 0.3),
            0 2px 8px rgba(0,0,0,0.1);
          padding: 12px;
          max-width: 600px;
          margin: 0 auto;
          width: 100%;
        }

        .intersection {
          cursor: pointer;
          transition: fill var(--duration-fast);
        }

        .intersection:hover {
          fill: rgba(0, 0, 0, 0.1);
        }

        .stone-placed {
          animation: stoneDrop 0.2s var(--ease-out);
        }

        @keyframes stoneDrop {
          from {
            transform: scale(0.8);
            opacity: 0;
          }
          to {
            transform: scale(1);
            opacity: 1;
          }
        }

        /* Game Controls */
        .game-controls {
          display: flex;
          justify-content: center;
          gap: 12px;
        }

        .game-control-btn {
          padding: 12px 24px;
          background: white;
          border: 1px solid var(--bg-tertiary);
          border-radius: var(--radius-md);
          font-family: var(--font-body);
          font-size: 0.875rem;
          font-weight: 500;
          cursor: pointer;
          transition: all var(--duration-fast) var(--ease-out);
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .game-control-btn:hover {
          background: var(--bg-secondary);
          border-color: var(--text-muted);
        }

        .game-control-btn.resign:hover {
          background: rgba(184, 84, 80, 0.1);
          border-color: var(--error);
          color: var(--error);
        }

        /* Move History */
        .move-history {
          background: white;
          border-radius: var(--radius-md);
          border: 1px solid var(--bg-tertiary);
          padding: 16px;
        }

        .move-history h4 {
          margin-bottom: 12px;
        }

        .moves-list {
          display: flex;
          flex-direction: column;
          gap: 6px;
          max-height: 200px;
          overflow-y: auto;
        }

        .move-item {
          display: flex;
          align-items: center;
          gap: 8px;
        }

        .move-color {
          width: 12px;
          height: 12px;
          border-radius: 50%;
        }

        .move-color.black {
          background: var(--stone-black);
        }

        .move-color.white {
          background: var(--stone-white);
          border: 1px solid var(--bg-tertiary);
        }

        /* Modal */
        .modal-overlay {
          position: fixed;
          inset: 0;
          background: rgba(0,0,0,0.5);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1000;
          animation: fadeIn 0.2s var(--ease-out);
        }

        .modal {
          background: white;
          border-radius: var(--radius-lg);
          padding: 32px;
          max-width: 400px;
          width: 90%;
          text-align: center;
          animation: scaleIn 0.2s var(--ease-out);
        }

        .modal h3 {
          margin-bottom: 8px;
        }

        .modal p {
          margin-bottom: 24px;
        }

        .modal-actions {
          display: flex;
          gap: 12px;
          justify-content: center;
        }

        /* Responsive */
        @media (max-width: 1024px) {
          .game-container {
            grid-template-columns: 1fr;
          }

          .player-panel {
            flex-direction: row;
            flex-wrap: wrap;
          }

          .player-card {
            flex: 1;
            min-width: 200px;
          }

          .move-history {
            display: none;
          }
        }
      `}</style>
    </div>
  );
};

// ═══════════════════════════════════════════════════════════════
// Profile Page
// ═══════════════════════════════════════════════════════════════

const ProfilePage = ({ setCurrentPage }) => {
  const stats = {
    rating: 1547,
    rank: '15 kyu',
    games: 234,
    wins: 142,
    losses: 92,
    winRate: 61,
  };

  const recentGames = [
    { opponent: 'StoneMaster', result: 'W', score: '+4.5', board: 9, time: '2h ago' },
    { opponent: 'GoPlayer99', result: 'L', score: 'R+3.5', board: 13, time: '5h ago' },
    { opponent: 'BlackDragon', result: 'W', score: '+12.5', board: 9, time: '1d ago' },
    { opponent: 'WhiteWolf', result: 'W', score: '+2.5', board: 19, time: '2d ago' },
    { opponent: 'TengenMaster', result: 'L', score: 'T+0.5', board: 13, time: '3d ago' },
  ];

  return (
    <div className="profile-page">
      <div className="container">
        <div className="profile-layout">
          {/* Profile Header */}
          <div className="profile-header-card">
            <div className="profile-avatar">
              <div className="avatar-placeholder">
                <Icons.User />
              </div>
            </div>
            <div className="profile-header-info">
              <h1 className="display-md">GuestPlayer</h1>
              <p className="text-muted">Joined January 2025</p>
              <div className="profile-badges">
                <span className="badge">Newcomer</span>
                <span className="badge">10 Games</span>
              </div>
            </div>
            <button className="btn btn-secondary">
              <Icons.Settings /> Edit Profile
            </button>
          </div>

          {/* Stats Grid */}
          <div className="stats-grid">
            <div className="stat-card primary">
              <span className="stat-card-value">{stats.rank}</span>
              <span className="stat-card-label">Current Rank</span>
            </div>
            <div className="stat-card">
              <span className="stat-card-value">{stats.rating}</span>
              <span className="stat-card-label">Rating</span>
            </div>
            <div className="stat-card">
              <span className="stat-card-value">{stats.games}</span>
              <span className="stat-card-label">Total Games</span>
            </div>
            <div className="stat-card">
              <span className="stat-card-value">{stats.winRate}%</span>
              <span className="stat-card-label">Win Rate</span>
            </div>
          </div>

          {/* Main Content */}
          <div className="profile-content">
            {/* Game History */}
            <div className="profile-section">
              <div className="section-header">
                <h2 className="text-lg">Recent Games</h2>
                <a href="#" className="text-sm" style={{ color: 'var(--accent)' }}>View All</a>
              </div>
              <div className="games-table">
                {recentGames.map((game, i) => (
                  <div key={i} className="game-row">
                    <div className={`game-result ${game.result === 'W' ? 'win' : 'loss'}`}>
                      {game.result}
                    </div>
                    <div className="game-opponent">
                      <span className="opponent-name">{game.opponent}</span>
                      <span className="text-xs text-muted">{game.board}×{game.board}</span>
                    </div>
                    <div className="game-score text-sm">{game.score}</div>
                    <div className="game-time text-sm text-muted">{game.time}</div>
                    <button className="btn btn-ghost btn-sm">Review</button>
                  </div>
                ))}
              </div>
            </div>

            {/* Sidebar */}
            <div className="profile-sidebar">
              <div className="profile-section">
                <h3 className="text-label">Win/Loss by Board</h3>
                <div className="board-stats">
                  <div className="board-stat-row">
                    <span>9×9</span>
                    <div className="board-stat-bar">
                      <div className="bar-fill win" style={{ width: '65%' }} />
                      <div className="bar-fill loss" style={{ width: '35%' }} />
                    </div>
                    <span className="text-sm">52-28</span>
                  </div>
                  <div className="board-stat-row">
                    <span>13×13</span>
                    <div className="board-stat-bar">
                      <div className="bar-fill win" style={{ width: '58%' }} />
                      <div className="bar-fill loss" style={{ width: '42%' }} />
                    </div>
                    <span className="text-sm">68-49</span>
                  </div>
                  <div className="board-stat-row">
                    <span>19×19</span>
                    <div className="board-stat-bar">
                      <div className="bar-fill win" style={{ width: '59%' }} />
                      <div className="bar-fill loss" style={{ width: '41%' }} />
                    </div>
                    <span className="text-sm">22-15</span>
                  </div>
                </div>
              </div>

              <div className="profile-section">
                <h3 className="text-label">Achievements</h3>
                <div className="achievements">
                  <div className="achievement">
                    <div className="achievement-icon">🎯</div>
                    <span className="text-sm">First Win</span>
                  </div>
                  <div className="achievement">
                    <div className="achievement-icon">🔥</div>
                    <span className="text-sm">5 Win Streak</span>
                  </div>
                  <div className="achievement locked">
                    <div className="achievement-icon">🏆</div>
                    <span className="text-sm">Tournament Win</span>
                  </div>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <style>{`
        .profile-page {
          padding: 100px 0 60px;
          min-height: 100vh;
          background: var(--bg-primary);
        }

        .profile-layout {
          display: flex;
          flex-direction: column;
          gap: 24px;
        }

        .profile-header-card {
          background: white;
          border-radius: var(--radius-lg);
          border: 1px solid var(--bg-tertiary);
          padding: 32px;
          display: flex;
          align-items: center;
          gap: 24px;
        }

        .profile-avatar {
          flex-shrink: 0;
        }

        .avatar-placeholder {
          width: 80px;
          height: 80px;
          background: var(--bg-secondary);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          color: var(--text-muted);
        }

        .avatar-placeholder svg {
          width: 40px;
          height: 40px;
        }

        .profile-header-info {
          flex: 1;
        }

        .profile-header-info h1 {
          margin-bottom: 4px;
        }

        .profile-badges {
          display: flex;
          gap: 8px;
          margin-top: 12px;
        }

        .badge {
          font-size: 0.75rem;
          padding: 4px 10px;
          background: var(--bg-secondary);
          border-radius: var(--radius-full);
          color: var(--text-secondary);
        }

        .stats-grid {
          display: grid;
          grid-template-columns: repeat(4, 1fr);
          gap: 16px;
        }

        .stat-card {
          background: white;
          border-radius: var(--radius-lg);
          border: 1px solid var(--bg-tertiary);
          padding: 24px;
          text-align: center;
        }

        .stat-card.primary {
          background: var(--stone-black);
          color: var(--stone-white);
          border-color: var(--stone-black);
        }

        .stat-card-value {
          display: block;
          font-family: var(--font-display);
          font-size: 2rem;
          font-weight: 500;
          margin-bottom: 4px;
        }

        .stat-card-label {
          font-size: 0.8125rem;
          color: var(--text-muted);
        }

        .stat-card.primary .stat-card-label {
          color: rgba(245, 242, 237, 0.7);
        }

        .profile-content {
          display: grid;
          grid-template-columns: 1fr 320px;
          gap: 24px;
        }

        .profile-section {
          background: white;
          border-radius: var(--radius-lg);
          border: 1px solid var(--bg-tertiary);
          padding: 24px;
        }

        .section-header {
          display: flex;
          justify-content: space-between;
          align-items: center;
          margin-bottom: 20px;
        }

        .section-header h2 {
          font-weight: 600;
        }

        .games-table {
          display: flex;
          flex-direction: column;
          gap: 12px;
        }

        .game-row {
          display: flex;
          align-items: center;
          gap: 16px;
          padding: 12px;
          background: var(--bg-secondary);
          border-radius: var(--radius-md);
        }

        .game-result {
          width: 28px;
          height: 28px;
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-size: 0.75rem;
          font-weight: 700;
        }

        .game-result.win {
          background: rgba(90, 143, 90, 0.15);
          color: var(--success);
        }

        .game-result.loss {
          background: rgba(184, 84, 80, 0.15);
          color: var(--error);
        }

        .game-opponent {
          flex: 1;
          display: flex;
          flex-direction: column;
        }

        .opponent-name {
          font-weight: 500;
        }

        .game-score {
          width: 60px;
        }

        .game-time {
          width: 60px;
        }

        /* Sidebar Stats */
        .profile-sidebar {
          display: flex;
          flex-direction: column;
          gap: 16px;
        }

        .profile-sidebar .profile-section {
          padding: 20px;
        }

        .profile-sidebar h3 {
          margin-bottom: 16px;
        }

        .board-stats {
          display: flex;
          flex-direction: column;
          gap: 12px;
        }

        .board-stat-row {
          display: flex;
          align-items: center;
          gap: 12px;
        }

        .board-stat-row > span:first-child {
          width: 50px;
          font-size: 0.875rem;
        }

        .board-stat-bar {
          flex: 1;
          height: 8px;
          background: var(--bg-tertiary);
          border-radius: var(--radius-full);
          display: flex;
          overflow: hidden;
        }

        .bar-fill.win {
          background: var(--success);
        }

        .bar-fill.loss {
          background: var(--error);
        }

        .achievements {
          display: flex;
          flex-direction: column;
          gap: 12px;
        }

        .achievement {
          display: flex;
          align-items: center;
          gap: 12px;
          padding: 12px;
          background: var(--bg-secondary);
          border-radius: var(--radius-md);
        }

        .achievement.locked {
          opacity: 0.5;
        }

        .achievement-icon {
          font-size: 1.25rem;
        }

        @media (max-width: 1024px) {
          .profile-content {
            grid-template-columns: 1fr;
          }

          .stats-grid {
            grid-template-columns: repeat(2, 1fr);
          }
        }

        @media (max-width: 640px) {
          .profile-header-card {
            flex-direction: column;
            text-align: center;
          }

          .stats-grid {
            grid-template-columns: 1fr 1fr;
          }
        }
      `}</style>
    </div>
  );
};

// ═══════════════════════════════════════════════════════════════
// Learn Page
// ═══════════════════════════════════════════════════════════════

const LearnPage = ({ setCurrentPage }) => {
  const lessons = [
    { id: 1, title: 'The Basics', desc: 'Learn the rules of Go', duration: '5 min', completed: true },
    { id: 2, title: 'Capturing Stones', desc: 'How to capture your opponent', duration: '8 min', completed: true },
    { id: 3, title: 'Territory & Scoring', desc: 'Understanding how to win', duration: '10 min', completed: false },
    { id: 4, title: 'Life and Death', desc: 'Keeping your groups alive', duration: '15 min', completed: false },
    { id: 5, title: 'Opening Strategy', desc: 'How to start a game', duration: '12 min', completed: false },
  ];

  return (
    <div className="learn-page">
      <div className="container">
        <div className="learn-header">
          <h1 className="display-lg">Learn Go</h1>
          <p className="text-lg text-muted">Master the ancient game with interactive lessons</p>
        </div>

        <div className="learn-layout">
          {/* Progress Card */}
          <div className="progress-card">
            <div className="progress-info">
              <h3>Your Progress</h3>
              <p className="text-muted text-sm">2 of 5 lessons complete</p>
            </div>
            <div className="progress-bar-container">
              <div className="progress-bar">
                <div className="progress-fill" style={{ width: '40%' }} />
              </div>
              <span className="progress-percent">40%</span>
            </div>
          </div>

          {/* Lessons List */}
          <div className="lessons-section">
            <h2 className="text-label">Beginner Course</h2>
            <div className="lessons-list">
              {lessons.map((lesson, i) => (
                <div
                  key={lesson.id}
                  className={`lesson-card ${lesson.completed ? 'completed' : ''}`}
                >
                  <div className="lesson-number">
                    {lesson.completed ? (
                      <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="3">
                        <polyline points="20 6 9 17 4 12" />
                      </svg>
                    ) : (
                      i + 1
                    )}
                  </div>
                  <div className="lesson-info">
                    <h3>{lesson.title}</h3>
                    <p className="text-sm text-muted">{lesson.desc}</p>
                  </div>
                  <div className="lesson-meta">
                    <span className="lesson-duration text-sm text-muted">
                      <Icons.Clock /> {lesson.duration}
                    </span>
                    <button className="btn btn-primary btn-sm">
                      {lesson.completed ? 'Review' : 'Start'}
                    </button>
                  </div>
                </div>
              ))}
            </div>
          </div>

          {/* Sidebar */}
          <div className="learn-sidebar">
            <div className="sidebar-section">
              <h3 className="text-label">Daily Puzzle</h3>
              <div className="puzzle-card-mini" onClick={() => setCurrentPage('puzzles')}>
                <MiniBoardPreview
                  size={9}
                  stones={[
                    { x: 2, y: 2, color: 'black' },
                    { x: 3, y: 2, color: 'white' },
                    { x: 2, y: 3, color: 'white' },
                    { x: 3, y: 3, color: 'black' },
                  ]}
                />
                <p className="text-sm" style={{ marginTop: '12px' }}>Black to play and capture</p>
                <button className="btn btn-secondary btn-sm" style={{ width: '100%', marginTop: '12px' }}>
                  Solve Puzzle
                </button>
              </div>
            </div>

            <div className="sidebar-section">
              <h3 className="text-label">Quick Tips</h3>
              <div className="tips-list">
                <div className="tip-item">
                  <span className="tip-icon">💡</span>
                  <p className="text-sm">Corners are easier to secure than edges</p>
                </div>
                <div className="tip-item">
                  <span className="tip-icon">💡</span>
                  <p className="text-sm">Don't follow your opponent everywhere</p>
                </div>
                <div className="tip-item">
                  <span className="tip-icon">💡</span>
                  <p className="text-sm">Two eyes mean life for your group</p>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>

      <style>{`
        .learn-page {
          padding: 100px 0 60px;
          min-height: 100vh;
          background: var(--bg-primary);
        }

        .learn-header {
          margin-bottom: 40px;
        }

        .learn-header h1 {
          margin-bottom: 8px;
        }

        .learn-layout {
          display: grid;
          grid-template-columns: 1fr 300px;
          gap: 24px;
        }

        .progress-card {
          grid-column: 1 / -1;
          background: white;
          border-radius: var(--radius-lg);
          border: 1px solid var(--bg-tertiary);
          padding: 24px;
          display: flex;
          align-items: center;
          justify-content: space-between;
          gap: 32px;
        }

        .progress-info h3 {
          font-family: var(--font-body);
          font-size: 1rem;
          font-weight: 600;
          margin-bottom: 4px;
        }

        .progress-bar-container {
          flex: 1;
          max-width: 400px;
          display: flex;
          align-items: center;
          gap: 16px;
        }

        .progress-bar {
          flex: 1;
          height: 8px;
          background: var(--bg-tertiary);
          border-radius: var(--radius-full);
          overflow: hidden;
        }

        .progress-fill {
          height: 100%;
          background: var(--accent);
          border-radius: var(--radius-full);
          transition: width 0.5s var(--ease-out);
        }

        .progress-percent {
          font-weight: 600;
          color: var(--accent);
        }

        .lessons-section {
          background: white;
          border-radius: var(--radius-lg);
          border: 1px solid var(--bg-tertiary);
          padding: 24px;
        }

        .lessons-section h2 {
          margin-bottom: 20px;
        }

        .lessons-list {
          display: flex;
          flex-direction: column;
          gap: 12px;
        }

        .lesson-card {
          display: flex;
          align-items: center;
          gap: 20px;
          padding: 20px;
          background: var(--bg-secondary);
          border-radius: var(--radius-md);
          border: 1px solid transparent;
          transition: all var(--duration-fast) var(--ease-out);
        }

        .lesson-card:hover {
          border-color: var(--bg-tertiary);
          background: white;
        }

        .lesson-card.completed {
          background: rgba(90, 143, 90, 0.08);
        }

        .lesson-card.completed .lesson-number {
          background: var(--success);
          color: white;
        }

        .lesson-number {
          width: 40px;
          height: 40px;
          background: var(--bg-tertiary);
          border-radius: 50%;
          display: flex;
          align-items: center;
          justify-content: center;
          font-weight: 600;
          flex-shrink: 0;
        }

        .lesson-info {
          flex: 1;
        }

        .lesson-info h3 {
          font-family: var(--font-body);
          font-size: 1rem;
          font-weight: 600;
          margin-bottom: 2px;
        }

        .lesson-meta {
          display: flex;
          align-items: center;
          gap: 16px;
        }

        .lesson-duration {
          display: flex;
          align-items: center;
          gap: 4px;
        }

        .lesson-duration svg {
          width: 14px;
          height: 14px;
        }

        .learn-sidebar {
          display: flex;
          flex-direction: column;
          gap: 20px;
        }

        .sidebar-section {
          background: white;
          border-radius: var(--radius-lg);
          border: 1px solid var(--bg-tertiary);
          padding: 20px;
        }

        .sidebar-section h3 {
          margin-bottom: 16px;
        }

        .puzzle-card-mini {
          cursor: pointer;
        }

        .tips-list {
          display: flex;
          flex-direction: column;
          gap: 12px;
        }

        .tip-item {
          display: flex;
          gap: 12px;
          align-items: flex-start;
        }

        .tip-icon {
          font-size: 1rem;
        }

        @media (max-width: 1024px) {
          .learn-layout {
            grid-template-columns: 1fr;
          }
        }
      `}</style>
    </div>
  );
};

// ═══════════════════════════════════════════════════════════════
// Auth Modal
// ═══════════════════════════════════════════════════════════════

const AuthModal = ({ mode, setMode, onClose, onAuth }) => {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [username, setUsername] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    onAuth({ email, username: username || 'GuestPlayer' });
  };

  return (
    <div className="auth-overlay" onClick={onClose}>
      <div className="auth-modal" onClick={e => e.stopPropagation()}>
        <button className="auth-close" onClick={onClose}>
          <Icons.X />
        </button>

        <div className="auth-header">
          <div className="nav-logo" style={{ justifyContent: 'center', marginBottom: '16px' }}>
            <div className="nav-logo-mark" />
            <span>Ishi</span>
          </div>
          <h2 className="display-md">
            {mode === 'login' ? 'Welcome back' : 'Create account'}
          </h2>
          <p className="text-muted">
            {mode === 'login'
              ? 'Sign in to continue playing'
              : 'Join the global Go community'}
          </p>
        </div>

        <form className="auth-form" onSubmit={handleSubmit}>
          {mode === 'register' && (
            <div className="form-group">
              <label className="label">Username</label>
              <input
                type="text"
                className="input input-lg"
                placeholder="Choose a username"
                value={username}
                onChange={e => setUsername(e.target.value)}
              />
            </div>
          )}

          <div className="form-group">
            <label className="label">Email</label>
            <input
              type="email"
              className="input input-lg"
              placeholder="you@example.com"
              value={email}
              onChange={e => setEmail(e.target.value)}
            />
          </div>

          <div className="form-group">
            <label className="label">Password</label>
            <input
              type="password"
              className="input input-lg"
              placeholder="••••••••"
              value={password}
              onChange={e => setPassword(e.target.value)}
            />
          </div>

          <button type="submit" className="btn btn-primary btn-lg" style={{ width: '100%' }}>
            {mode === 'login' ? 'Sign In' : 'Create Account'}
          </button>
        </form>

        <div className="auth-divider">
          <span>or</span>
        </div>

        <button className="btn btn-secondary" style={{ width: '100%' }} onClick={handleSubmit}>
          Continue as Guest
        </button>

        <p className="auth-switch text-sm text-muted">
          {mode === 'login' ? (
            <>
              Don't have an account?{' '}
              <a href="#" onClick={(e) => { e.preventDefault(); setMode('register'); }}>
                Sign up
              </a>
            </>
          ) : (
            <>
              Already have an account?{' '}
              <a href="#" onClick={(e) => { e.preventDefault(); setMode('login'); }}>
                Sign in
              </a>
            </>
          )}
        </p>
      </div>

      <style>{`
        .auth-overlay {
          position: fixed;
          inset: 0;
          background: rgba(0,0,0,0.6);
          display: flex;
          align-items: center;
          justify-content: center;
          z-index: 1000;
          animation: fadeIn 0.2s var(--ease-out);
          padding: 20px;
        }

        .auth-modal {
          background: white;
          border-radius: var(--radius-xl);
          padding: 40px;
          max-width: 420px;
          width: 100%;
          position: relative;
          animation: scaleIn 0.3s var(--ease-out);
        }

        .auth-close {
          position: absolute;
          top: 16px;
          right: 16px;
          background: none;
          border: none;
          color: var(--text-muted);
          cursor: pointer;
          padding: 8px;
          border-radius: 50%;
          transition: all var(--duration-fast);
        }

        .auth-close:hover {
          background: var(--bg-secondary);
          color: var(--text-primary);
        }

        .auth-header {
          text-align: center;
          margin-bottom: 32px;
        }

        .auth-header h2 {
          margin-bottom: 8px;
        }

        .auth-form {
          margin-bottom: 24px;
        }

        .auth-divider {
          display: flex;
          align-items: center;
          gap: 16px;
          margin: 24px 0;
          color: var(--text-muted);
          font-size: 0.875rem;
        }

        .auth-divider::before,
        .auth-divider::after {
          content: '';
          flex: 1;
          height: 1px;
          background: var(--bg-tertiary);
        }

        .auth-switch {
          text-align: center;
          margin-top: 24px;
        }

        .auth-switch a {
          color: var(--accent);
          text-decoration: none;
          font-weight: 500;
        }

        .auth-switch a:hover {
          text-decoration: underline;
        }
      `}</style>
    </div>
  );
};

// ═══════════════════════════════════════════════════════════════
// Main App Component
// ═══════════════════════════════════════════════════════════════

const App = () => {
  const [currentPage, setCurrentPage] = useState('landing');
  const [user, setUser] = useState(null);
  const [showAuth, setShowAuth] = useState(null);
  const [gameConfig, setGameConfig] = useState(null);

  const startGame = (config) => {
    setGameConfig(config);
    setCurrentPage('game');
  };

  const handleAuth = (userData) => {
    setUser(userData);
    setShowAuth(null);
  };

  return (
    <AppContext.Provider value={{ user, setUser, currentPage, setCurrentPage }}>
      <Navigation
        currentPage={currentPage}
        setCurrentPage={setCurrentPage}
        user={user}
        setShowAuth={setShowAuth}
      />

      {currentPage === 'landing' && (
        <LandingPage
          setCurrentPage={setCurrentPage}
          setShowAuth={setShowAuth}
        />
      )}

      {currentPage === 'play' && (
        <PlayPage
          setCurrentPage={setCurrentPage}
          startGame={startGame}
        />
      )}

      {currentPage === 'game' && (
        <GamePage
          gameConfig={gameConfig}
          setCurrentPage={setCurrentPage}
        />
      )}

      {currentPage === 'profile' && (
        <ProfilePage setCurrentPage={setCurrentPage} />
      )}

      {currentPage === 'learn' && (
        <LearnPage setCurrentPage={setCurrentPage} />
      )}

      {showAuth && (
        <AuthModal
          mode={showAuth}
          setMode={setShowAuth}
          onClose={() => setShowAuth(null)}
          onAuth={handleAuth}
        />
      )}
    </AppContext.Provider>
  );
};

// ═══════════════════════════════════════════════════════════════
// Render
// ═══════════════════════════════════════════════════════════════

const root = ReactDOM.createRoot(document.getElementById('app'));
root.render(<App />);
