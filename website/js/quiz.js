/**
 * quiz.js — QuizCert Pro quiz engine
 * Handles: quiz loading, question navigation, timer, answer tracking, submission
 */

let quiz       = null;
let answers    = {};     // { question_id: selected_option_id }
let curIdx     = 0;
let timerSecs  = 0;
let timerInterval = null;
let BASE_URL   = '';
let QUIZ_ID    = 0;
let submitted  = false;

function initQuiz(quizId, base) {
  QUIZ_ID   = quizId;
  BASE_URL  = base;
  fetchQuiz();
}

async function fetchQuiz() {
  try {
    const res  = await fetch(`${BASE_URL}/quiz_api.php?action=get&quiz_id=${QUIZ_ID}`);
    quiz = await res.json();
    if (quiz.error) throw quiz.error;
    renderQuiz();
  } catch(e) {
    document.getElementById('loadingOverlay').innerHTML = `
      <div class="alert alert-error">❌ Failed to load quiz. <a href="${BASE_URL}/home.php" style="color:#fff">← Back</a></div>`;
  }
}

function renderQuiz() {
  document.getElementById('loadingOverlay').style.display = 'none';
  document.getElementById('quizWrapper').style.display = 'block';

  document.title = quiz.title + ' — QuizCert Pro';
  document.getElementById('quizTitle').textContent = quiz.title;
  document.getElementById('nav-quiz-title').textContent = quiz.title;

  // Shuffle questions order (optional: keep original)
  // quiz.questions = shuffle(quiz.questions);

  timerSecs = parseInt(quiz.time_limit) || 600;
  startTimer();
  showQuestion(0);
}

/* ── Timer ─────────────────────────────────────────────────────── */
function startTimer() {
  updateTimerDisplay();
  timerInterval = setInterval(() => {
    timerSecs--;
    updateTimerDisplay();
    if (timerSecs <= 60) {
      document.getElementById('timer').classList.add('warning');
    }
    if (timerSecs <= 0) {
      clearInterval(timerInterval);
      submitQuiz(true);
    }
  }, 1000);
}

function updateTimerDisplay() {
  const m = String(Math.floor(timerSecs / 60)).padStart(2, '0');
  const s = String(timerSecs % 60).padStart(2, '0');
  document.getElementById('timer').textContent = `⏱ ${m}:${s}`;
}

/* ── Questions ──────────────────────────────────────────────────── */
function showQuestion(idx) {
  if (!quiz) return;
  curIdx = Math.max(0, Math.min(idx, quiz.questions.length - 1));
  const q     = quiz.questions[curIdx];
  const total = quiz.questions.length;

  // Progress
  document.getElementById('progLabel').textContent = `Question ${curIdx+1} of ${total}`;
  document.getElementById('answeredLabel').textContent = `${Object.keys(answers).length} answered`;
  document.getElementById('progFill').style.width = `${((curIdx+1)/total)*100}%`;

  // Nav buttons
  document.getElementById('btnPrev').disabled = curIdx === 0;
  const isLast = curIdx === total - 1;
  document.getElementById('btnNext').classList.toggle('hidden', isLast);
  document.getElementById('btnSubmit').classList.toggle('hidden', !isLast);

  // Question HTML
  const optHtml = q.options.map(opt => `
    <label class="option-item ${answers[q.id] == opt.id ? 'selected' : ''}"
           onclick="selectOption(${q.id}, ${opt.id}, this)">
      <input type="radio" name="q${q.id}" value="${opt.id}"
             ${answers[q.id] == opt.id ? 'checked' : ''}>
      <div class="option-dot"></div>
      <span class="option-label">${escHtml(opt.option_text)}</span>
    </label>
  `).join('');

  document.getElementById('questionArea').innerHTML = `
    <div class="question-card">
      <div class="question-number">Question ${curIdx+1} · ${q.question_type === 'truefalse' ? 'True / False' : 'Multiple Choice'}</div>
      <div class="question-text">${escHtml(q.question_text)}</div>
      <div class="options-list">${optHtml}</div>
    </div>
  `;
}

function selectOption(qid, oid, labelEl) {
  answers[qid] = oid;
  // Update UI
  document.querySelectorAll(`.option-item`).forEach(el => el.classList.remove('selected'));
  labelEl.classList.add('selected');
  document.getElementById('answeredLabel').textContent = `${Object.keys(answers).length} answered`;
}

function navigate(dir) {
  showQuestion(curIdx + dir);
}

/* ── Submit ─────────────────────────────────────────────────────── */
async function submitQuiz(timeout = false) {
  if (submitted) return;

  if (!timeout) {
    const unanswered = quiz.questions.length - Object.keys(answers).length;
    if (unanswered > 0) {
      const go = confirm(`You have ${unanswered} unanswered question(s). Submit anyway?`);
      if (!go) return;
    }
  }

  submitted = true;
  clearInterval(timerInterval);

  // Show loading overlay
  document.getElementById('loadingOverlay').style.display = 'flex';
  document.getElementById('loadingOverlay').innerHTML = `
    <div class="spinner"></div>
    <div class="loading-text">Evaluating your answers…</div>`;

  try {
    const res = await fetch(`${BASE_URL}/quiz_api.php?action=submit`, {
      method: 'POST',
      headers: {'Content-Type': 'application/json'},
      body: JSON.stringify({ quiz_id: QUIZ_ID, answers })
    });
    const result = await res.json();
    if (result.error) throw result.error;

    // Store result in sessionStorage and redirect
    sessionStorage.setItem('quizResult', JSON.stringify(result));
    window.location.href = `${BASE_URL}/result.php`;

  } catch(e) {
    document.getElementById('loadingOverlay').innerHTML = `
      <div class="alert alert-error" style="max-width:400px">
        ❌ Submission failed: ${escHtml(String(e))}
        <br><a href="${BASE_URL}/home.php" style="color:#fff;margin-top:.5rem;display:block">← Back to Home</a>
      </div>`;
  }
}

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str;
  return d.innerHTML;
}
