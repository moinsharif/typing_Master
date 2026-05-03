const WORD_LISTS = {
    'very-easy': ['the', 'of', 'and', 'a', 'to', 'in', 'is', 'you', 'that', 'it', 'he', 'was', 'for', 'on', 'are', 'as', 'with', 'his', 'they', 'at', 'be', 'this', 'have', 'from', 'or', 'one', 'had', 'by', 'word', 'but', 'not', 'what', 'all', 'were', 'we', 'when', 'your', 'can', 'said', 'there', 'use', 'an', 'each', 'which', 'she', 'do', 'how', 'their', 'if'],
    'easy': ['about', 'many', 'then', 'them', 'these', 'so', 'some', 'her', 'would', 'make', 'like', 'him', 'into', 'time', 'has', 'look', 'two', 'more', 'write', 'go', 'see', 'number', 'no', 'way', 'could', 'people', 'my', 'than', 'first', 'water', 'been', 'called', 'who', 'oil', 'its', 'now', 'find', 'long', 'down', 'day', 'did', 'get', 'come', 'made', 'may', 'part'],
    'medium': ['mountain', 'discover', 'beautiful', 'wonderful', 'fountain', 'building', 'tomorrow', 'computer', 'science', 'history', 'language', 'country', 'important', 'example', 'experience', 'together', 'problem', 'thought', 'through', 'between', 'sentence', 'difference', 'possible', 'government', 'interest', 'process', 'increase', 'surface', 'material', 'special', 'natural', 'general', 'current', 'provide', 'suggest', 'develop'],
    'hard': ['encyclopedia', 'characteristic', 'sophisticated', 'unbelievable', 'extraordinary', 'implementation', 'communication', 'organization', 'relationship', 'development', 'environment', 'information', 'knowledge', 'perspective', 'philosophy', 'psychology', 'technology', 'understanding', 'visualization', 'opportunity', 'competition', 'consequence', 'destination', 'exploration', 'imagination', 'innovation', 'observation', 'participation'],
    'very-hard': ['pneumonoultramicroscopicsilicovolcanoconiosis', 'floccinaucinihilipilification', 'antidisestablishmentarianism', 'honorificabilitudinitatibus', 'spectrophotofluorometrically', 'pseudopseudohypoparathyroidism', 'psychoneuroendocrinoimmunology', 'hepaticocholangiogastroenterostomy', 'radioimmunoelectrophoresis', 'thyroparathyroidectomized', 'dichlorodiphenyltrichloroethane']
};

const KEYBOARD_LAYOUT = [
    ['q', 'w', 'e', 'r', 't', 'y', 'u', 'i', 'o', 'p'],
    ['a', 's', 'd', 'f', 'g', 'h', 'j', 'k', 'l'],
    ['z', 'x', 'c', 'v', 'b', 'n', 'm'],
    ['space']
];

const FINGER_MAP = {
    'q': 'l-pinky', 'a': 'l-pinky', 'z': 'l-pinky',
    'w': 'l-ring', 's': 'l-ring', 'x': 'l-ring',
    'e': 'l-middle', 'd': 'l-middle', 'c': 'l-middle',
    'r': 'l-index', 'f': 'l-index', 'v': 'l-index',
    't': 'l-index', 'g': 'l-index', 'b': 'l-index',
    'y': 'r-index', 'h': 'r-index', 'n': 'r-index',
    'u': 'r-index', 'j': 'r-index', 'm': 'r-index',
    'i': 'r-middle', 'k': 'r-middle',
    'o': 'r-ring', 'l': 'r-ring',
    'p': 'r-pinky',
    'space': 'r-thumb' // or l-thumb
};

class TypeMaster {
    constructor() {
        this.currentLevel = 'very-easy';
        this.selectedDuration = 60; // Default
        this.words = [];
        this.currentWordIndex = 0;
        this.currentLetterIndex = 0;
        this.startTime = null;
        this.timer = null;
        this.timeLeft = 60;
        this.isActive = false;
        this.user = null;
        this.stats = {
            correct: 0,
            wrong: 0,
            keyTimings: {},
            lastKeyTime: null
        };

        this.init();
    }

    async init() {
        console.log('TypeMaster System Initializing...');
        this.renderKeyboard();
        this.setupEventListeners();
        this.loadWords();
        this.updateUI();
        
        // Use PHP session data if available
        if (window.TM_USER) {
            this.user = window.TM_USER;
            console.log('JS User Context:', this.user);
            this.updateAuthUI();
        } else {
            console.warn('No User Context Found');
        }
    }

    renderKeyboard() {
        const kb = document.querySelector('.keyboard');
        kb.innerHTML = '';
        KEYBOARD_LAYOUT.forEach(row => {
            const rowEl = document.createElement('div');
            rowEl.className = 'kb-row';
            row.forEach(key => {
                const keyEl = document.createElement('div');
                const fingerId = FINGER_MAP[key.toLowerCase()];
                const colorClass = fingerId ? `key-${fingerId}` : '';
                keyEl.className = `key ${key === 'space' ? 'space' : ''} color-hint ${colorClass}`;
                keyEl.textContent = key === 'space' ? '' : key;
                keyEl.dataset.key = key;
                rowEl.appendChild(keyEl);
            });
            kb.appendChild(rowEl);
        });
    }

    setupEventListeners() {
        const input = document.getElementById('typing-input');
        input.addEventListener('input', (e) => this.handleInput(e));
        input.addEventListener('keydown', (e) => this.handleKeyDown(e));

        document.querySelectorAll('.diff-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.diff-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.currentLevel = btn.dataset.level;
                this.reset();
            });
        });

        document.querySelectorAll('.time-btn').forEach(btn => {
            btn.addEventListener('click', () => {
                document.querySelectorAll('.time-btn').forEach(b => b.classList.remove('active'));
                btn.classList.add('active');
                this.selectedDuration = parseInt(btn.dataset.time);
                this.reset();
            });
        });

        document.getElementById('restart-btn').addEventListener('click', () => this.reset());

        // Modals
        const loginTrigger = document.getElementById('login-trigger');
        if (loginTrigger) {
            loginTrigger.addEventListener('click', () => this.toggleModal('auth-modal', true));
        }

        const lbBtn = document.getElementById('leaderboard-btn');
        if (lbBtn) {
            lbBtn.addEventListener('click', () => this.toggleModal('leaderboard-modal', true));
        }

        const adminBtn = document.getElementById('admin-btn');
        if (adminBtn) {
            adminBtn.addEventListener('click', () => this.toggleModal('admin-modal', true));
        }
        
        document.querySelectorAll('.close-modal').forEach(btn => {
            btn.addEventListener('click', (e) => this.toggleModal(e.target.closest('.modal').id, false));
        });

        document.getElementById('auth-toggle').addEventListener('click', (e) => {
            e.preventDefault();
            const title = document.getElementById('modal-title');
            const submitBtn = document.querySelector('.submit-btn');
            title.textContent = title.textContent === 'Welcome Back' ? 'Create Account' : 'Welcome Back';
            submitBtn.textContent = title.textContent === 'Welcome Back' ? 'Continue' : 'Register';
            e.target.textContent = title.textContent === 'Welcome Back' ? 'Sign Up' : 'Sign In';
        });

        document.getElementById('auth-form').addEventListener('submit', (e) => this.handleAuth(e));
    }

    async handleAuth(e) {
        e.preventDefault();
        const username = document.getElementById('username').value;
        const password = document.getElementById('password').value;
        const isLogin = document.getElementById('modal-title').textContent === 'Welcome Back';
        const action = isLogin ? 'login' : 'register';

        try {
            const resp = await fetch(`api.php?action=${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ username, password })
            });
            const result = await resp.json();

            if (result.success) {
                if (isLogin) {
                    location.reload(); // Refresh to sync PHP session
                } else {
                    alert('Registration successful! Please login.');
                    document.getElementById('auth-toggle').click();
                }
            } else {
                alert(result.message);
            }
        } catch (err) {
            console.error('Auth error:', err);
        }
    }

    updateAuthUI() {
        const authSection = document.getElementById('auth-section');
        const adminBtn = document.getElementById('admin-btn');

        if (this.user) {
            // Already handled by PHP for initial load, but this handles JS updates
            if (this.user.is_admin && adminBtn) {
                adminBtn.classList.remove('hidden');
            }
            
            const logoutBtn = document.getElementById('logout-btn');
            if (logoutBtn) {
                logoutBtn.addEventListener('click', () => this.logout());
            }
        }
    }

    async logout() {
        await fetch('api.php?action=logout');
        location.reload(); // Refresh to sync PHP session
    }

    async toggleModal(id, show) {
        const modal = document.getElementById(id);
        if (!modal) return;
        
        if (show) {
            modal.classList.remove('hidden');
            // Small delay to allow 'hidden' (display: none) to be removed before 'visible' (opacity) starts
            setTimeout(() => modal.classList.add('visible'), 10);
            
            if (id === 'leaderboard-modal') this.loadLeaderboard();
            if (id === 'admin-modal') this.loadAdminStats();
        } else {
            modal.classList.remove('visible');
            // Wait for transition to finish before adding 'hidden'
            setTimeout(() => modal.classList.add('hidden'), 300);
        }
    }

    async loadLeaderboard() {
        const list = document.querySelector('.leaderboard-list');
        try {
            const resp = await fetch('api.php?action=get_leaderboard');
            const data = await resp.json();
            list.innerHTML = `
                <div class="lb-header">
                    <span>Rank</span>
                    <span>Name</span>
                    <span>WPM</span>
                    <span>ACC</span>
                </div>
                ${data.map((d, i) => `
                    <div class="lb-row">
                        <span>#${i + 1}</span>
                        <span>${d.username}</span>
                        <span>${d.wpm}</span>
                        <span>${d.accuracy}%</span>
                    </div>
                `).join('')}
            `;
        } catch (e) { console.error(e); }
    }

    async loadAdminStats() {
        const body = document.getElementById('admin-stats-body');
        try {
            const resp = await fetch('api.php?action=get_admin_stats');
            const data = await resp.json();
            if (data.success === false) {
                body.innerHTML = `<tr><td colspan="5">Unauthorized access</td></tr>`;
                return;
            }
            body.innerHTML = data.map(u => `
                <tr>
                    <td><b class="user-link" onclick="window.TM.loadUserAnalysis('${u.username}')">${u.username}</b></td>
                    <td>${u.sessions}</td>
                    <td>${Math.round(u.avg_wpm || 0)}</td>
                    <td>${u.max_wpm || 0}</td>
                    <td>${Math.round(u.avg_acc || 0)}%</td>
                </tr>
            `).join('');
        } catch (e) { console.error(e); }
    }

    async loadUserAnalysis(username) {
        this.toggleModal('user-detail-modal', true);
        document.getElementById('detail-user-name').textContent = `Performance Report: ${username}`;
        const heatmap = document.getElementById('user-heatmap');
        const adviceBox = document.getElementById('user-advice-box');
        heatmap.innerHTML = 'Loading analysis...';
        adviceBox.innerHTML = '';

        try {
            const resp = await fetch(`api.php?action=get_user_analysis&username=${username}`);
            const data = await resp.json();
            if (!data.success) return;

            heatmap.innerHTML = '';
            const slowKeys = [];
            
            for (const [key, avg] of Object.entries(data.analysis)) {
                const keyEl = document.createElement('div');
                keyEl.className = 'heatmap-key';
                keyEl.innerHTML = `${key}<br><small>${Math.round(avg)}ms</small>`;
                
                if (avg > 400) {
                    keyEl.style.background = 'rgba(248, 113, 113, 0.3)';
                    slowKeys.push({key, avg});
                } else if (avg > 200) {
                    keyEl.style.background = 'rgba(251, 191, 36, 0.3)';
                } else {
                    keyEl.style.background = 'rgba(74, 222, 128, 0.3)';
                }
                heatmap.appendChild(keyEl);
            }

            if (slowKeys.length > 0) {
                slowKeys.sort((a, b) => b.avg - a.avg);
                const topSlow = slowKeys.slice(0, 3).map(k => `"${k.key.toUpperCase()}"`).join(', ');
                adviceBox.innerHTML = `<p>This learner is struggling with: <b>${topSlow}</b>. Suggest more practice on these keys.</p>`;
            } else {
                adviceBox.innerHTML = `<p>This learner has consistent speed across all keys. Excellent progress!</p>`;
            }
        } catch (e) { console.error(e); }
    }

    loadWords() {
        const list = WORD_LISTS[this.currentLevel];
        this.words = [];
        for (let i = 0; i < 500; i++) {
            this.words.push(list[Math.floor(Math.random() * list.length)]);
        }
        this.renderWords();
    }

    renderWords() {
        const container = document.getElementById('words-display');
        container.innerHTML = '';
        this.words.forEach((word, idx) => {
            const wordEl = document.createElement('div');
            wordEl.className = `word ${idx === 0 ? 'current' : ''}`;
            word.split('').forEach(char => {
                const charEl = document.createElement('span');
                charEl.className = 'letter';
                charEl.textContent = char;
                wordEl.appendChild(charEl);
            });
            container.appendChild(wordEl);
        });
        this.highlightNextKey();
    }

    handleInput(e) {
        if (!this.isActive && e.target.value.length > 0) {
            this.start();
        }

        const typed = e.target.value;
        const currentWord = this.words[this.currentWordIndex];
        const wordEl = document.querySelectorAll('.word')[this.currentWordIndex];
        if (!wordEl) return;
        const letters = wordEl.querySelectorAll('.letter');

        const now = performance.now();
        if (this.stats.lastKeyTime) {
            const diff = now - this.stats.lastKeyTime;
            const lastChar = typed[typed.length - 1];
            if (lastChar && lastChar !== ' ') {
                if (!this.stats.keyTimings[lastChar]) this.stats.keyTimings[lastChar] = [];
                this.stats.keyTimings[lastChar].push(diff);
            }
        }
        this.stats.lastKeyTime = now;

        if (typed.endsWith(' ')) {
            const wordToCompare = typed.trim();
            if (wordToCompare === currentWord) {
                wordEl.className = 'word correct';
                this.stats.correct++;
            } else {
                wordEl.className = 'word wrong';
                this.stats.wrong++;
            }
            this.currentWordIndex++;
            this.currentLetterIndex = 0;
            e.target.value = '';
            
            if (this.currentWordIndex >= this.words.length) {
                this.finish();
            } else {
                this.updateActiveWord();
            }
        } else {
            letters.forEach((span, idx) => {
                if (idx < typed.length) {
                    span.className = typed[idx] === currentWord[idx] ? 'letter correct' : 'letter wrong';
                } else {
                    span.className = 'letter';
                }
            });
            this.currentLetterIndex = typed.length;
        }
        
        this.updateStats();
        this.highlightNextKey();
    }

    handleKeyDown(e) {
        const keyEl = document.querySelector(`.key[data-key="${e.key.toLowerCase()}"]`) || 
                      (e.key === ' ' ? document.querySelector('.key.space') : null);
        if (keyEl) {
            keyEl.classList.add('pressed');
            setTimeout(() => keyEl.classList.remove('pressed'), 100);
        }
    }

    highlightNextKey() {
        document.querySelectorAll('.key').forEach(k => k.classList.remove('highlight'));
        document.querySelectorAll('.finger-group').forEach(f => f.classList.remove('highlight'));
        
        if (this.currentLevel !== 'very-easy') return;

        const currentWord = this.words[this.currentWordIndex];
        const typed = document.getElementById('typing-input').value;
        
        let nextChar = (typed.length < currentWord.length) ? currentWord[typed.length] : 'space';
        const keyEl = document.querySelector(`.key[data-key="${nextChar.toLowerCase()}"]`) || 
                      (nextChar === 'space' ? document.querySelector('.key.space') : null);
        
        if (keyEl) {
            keyEl.classList.add('highlight');
            const fingerId = FINGER_MAP[nextChar.toLowerCase()];
            if (fingerId) {
                const fingerEl = document.querySelector(`.finger-group[data-finger="${fingerId}"]`);
                if (fingerEl) fingerEl.classList.add('highlight');
            }
        }
    }

    updateActiveWord() {
        const words = document.querySelectorAll('.word');
        words.forEach(w => w.classList.remove('current'));
        if (words[this.currentWordIndex]) {
            words[this.currentWordIndex].classList.add('current');
            
            // Localized scrolling for the words container only
            const container = document.getElementById('words-display');
            const activeWord = words[this.currentWordIndex];
            if (activeWord.offsetTop > container.offsetHeight + container.scrollTop - 40) {
                container.scrollTop = activeWord.offsetTop - 40;
            }
        }
    }

    start() {
        this.isActive = true;
        this.startTime = Date.now();
        this.timer = setInterval(() => {
            this.timeLeft--;
            if (this.timeLeft <= 0) this.finish();
            this.updateUI();
        }, 1000);
    }

    async finish() {
        clearInterval(this.timer);
        this.isActive = false;
        document.getElementById('typing-input').disabled = true;
        
        const total = this.stats.correct + this.stats.wrong;
        const accuracy = total === 0 ? 0 : Math.round((this.stats.correct / total) * 100);
        const elapsed = (this.selectedDuration - this.timeLeft) / 60;
        const wpm = elapsed === 0 ? 0 : Math.round(this.stats.correct / elapsed);

        console.log('Test Finished:', { wpm, accuracy, totalWords: this.stats.correct });

        if (this.user) {
            console.log('Saving result for user:', this.user.username);
            await fetch('api.php?action=save_result', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    wpm,
                    accuracy,
                    difficulty: this.currentLevel,
                    keyAnalysis: this.stats.keyTimings
                })
            });
        }

        this.showAnalysis();
    }

    reset() {
        clearInterval(this.timer);
        this.isActive = false;
        this.timeLeft = this.selectedDuration;
        this.currentWordIndex = 0;
        this.currentLetterIndex = 0;
        this.stats = { correct: 0, wrong: 0, keyTimings: {}, lastKeyTime: null };
        document.getElementById('typing-input').value = '';
        document.getElementById('typing-input').disabled = false;
        document.getElementById('typing-input').focus();
        document.getElementById('analysis-panel').classList.add('hidden');
        this.loadWords();
        this.updateUI();
    }

    updateUI() {
        document.getElementById('timer-val').textContent = `${this.timeLeft}s`;
        this.updateStats();
    }

    updateStats() {
        const total = this.stats.correct + this.stats.wrong;
        const accuracy = total === 0 ? 0 : Math.round((this.stats.correct / total) * 100);
        document.getElementById('acc-val').textContent = `${accuracy}%`;
        const elapsed = (this.selectedDuration - this.timeLeft) / 60;
        const wpm = elapsed === 0 ? 0 : Math.round(this.stats.correct / elapsed);
        document.getElementById('wpm-val').textContent = wpm;
    }

    showAnalysis() {
        const panel = document.getElementById('analysis-panel');
        panel.classList.remove('hidden');
        const heatmap = document.getElementById('key-heatmap');
        heatmap.innerHTML = '';
        const slowKeys = [];
        
        for (const [key, times] of Object.entries(this.stats.keyTimings)) {
            const avg = times.reduce((a, b) => a + b, 0) / times.length;
            const keyEl = document.createElement('div');
            keyEl.className = 'heatmap-key';
            keyEl.innerHTML = `${key}<br><small>${Math.round(avg)}ms</small>`;
            if (avg > 400) {
                keyEl.style.background = 'rgba(248, 113, 113, 0.3)';
                slowKeys.push({key, avg});
            } else if (avg > 200) {
                keyEl.style.background = 'rgba(251, 191, 36, 0.3)';
            } else {
                keyEl.style.background = 'rgba(74, 222, 128, 0.3)';
            }
            heatmap.appendChild(keyEl);
        }

        const adviceBox = document.getElementById('advice-box');
        if (slowKeys.length > 0) {
            slowKeys.sort((a, b) => b.avg - a.avg);
            const topSlow = slowKeys.slice(0, 3).map(k => `"${k.key.toUpperCase()}"`).join(', ');
            adviceBox.innerHTML = `<h4>Personalized Advice</h4><p>You are taking more time on: <b>${topSlow}</b>. Practice these more!</p>`;
        } else {
            adviceBox.innerHTML = `<h4>Great Job!</h4><p>Your typing is consistent. Try higher difficulty!</p>`;
        }
        panel.scrollIntoView({ behavior: 'smooth' });
    }
}

window.addEventListener('DOMContentLoaded', () => {
    window.TM = new TypeMaster();
});
