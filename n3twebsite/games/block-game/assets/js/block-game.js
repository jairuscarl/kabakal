(() => {
  'use strict';

  const SIZE = 8;
  const COLORS = [
    ['#20e6ff', '#3d7cff'], ['#a855f7', '#ff4ecd'], ['#9cff42', '#18d26e'],
    ['#ff9f1c', '#ff4d6d'], ['#ffe45c', '#ff9f1c'], ['#3d7cff', '#a855f7'], ['#ff4ecd', '#ff4d6d']
  ];
  const BLOCKS = [
    {name:'single', tier:'rare', cells:[[0,0]], min:0, base:.02, high:.035, recovery:true},
    {name:'line2', tier:'standard', cells:[[0,0],[1,0]], min:0, base:.15, recovery:true},
    {name:'line3', tier:'standard', cells:[[0,0],[1,0],[2,0]], min:0, base:.14, recovery:true},
    {name:'line4', tier:'standard', cells:[[0,0],[1,0],[2,0],[3,0]], min:0, base:.1, recovery:true},
    {name:'line5', tier:'standard', cells:[[0,0],[1,0],[2,0],[3,0],[4,0]], min:150, base:.045, recovery:true},
    {name:'square2', tier:'standard', cells:[[0,0],[1,0],[0,1],[1,1]], min:0, base:.13},
    {name:'rect23', tier:'standard', cells:[[0,0],[1,0],[0,1],[1,1],[0,2],[1,2]], min:120, base:.055},
    {name:'square3', tier:'heavy', cells:[[0,0],[1,0],[2,0],[0,1],[1,1],[2,1],[0,2],[1,2],[2,2]], min:700, base:.006, high:.025},
    {name:'smallL', tier:'standard', cells:[[0,0],[0,1],[1,1]], min:0, base:.095},
    {name:'normalL', tier:'standard', cells:[[0,0],[0,1],[0,2],[1,2]], min:80, base:.075},
    {name:'smallT', tier:'mid', cells:[[0,0],[1,0],[2,0],[1,1]], min:150, base:.055, high:.075},
    {name:'sShape', tier:'mid', cells:[[1,0],[2,0],[0,1],[1,1]], min:120, base:.05, high:.07},
    {name:'zShape', tier:'mid', cells:[[0,0],[1,0],[1,1],[2,1]], min:120, base:.05, high:.07},
    {name:'cross', tier:'rare', cells:[[1,0],[0,1],[1,1],[2,1],[1,2]], min:550, base:.008, high:.03, impact:true}
  ];

  const state = {
    board: Array(SIZE * SIZE).fill(null), pieces: [], selected: null, score: 0, best: 0,
    combo: 1, maxCombo: 1, lines: 0, cellsPlaced: 0, events: 0, startedAt: Date.now(), over: false, muted: false, dragGhost: null, blocksUsedThisRound: 0
  };

  let lastPreviewCells = [];
  let lastScoreLineCells = [];
  let lastPreviewKey = '';

  const $ = (id) => document.getElementById(id);
  const els = {
    board: $('board'), tray: $('pieceTray'), score: $('score'), best: $('bestScore'), combo: $('combo'), lines: $('lines'),
    save: $('saveState'), float: $('floatingText'), leaders: $('leaderboardList'), rank: $('playerRank'), modal: $('gameOverModal'),
    final: $('finalScore'), modalBest: $('modalBest'), modalRank: $('modalRank'), particles: $('particles')
  };

  function api(path, data = null) {
    return fetch(window.BLOCK_GAME.apiBase + path, {
      method: data ? 'POST' : 'GET', headers: data ? {'Content-Type': 'application/json'} : {},
      body: data ? JSON.stringify({ csrf_token: window.BLOCK_GAME.csrf, ...data }) : null, credentials: 'same-origin'
    }).then(r => r.json());
  }

  function beep(type = 'place') {
    if (state.muted) return;
    try {
      const ctx = new (window.AudioContext || window.webkitAudioContext)();
      const o = ctx.createOscillator(), g = ctx.createGain();
      const freq = type === 'clear' ? 740 : type === 'combo' ? 980 : type === 'over' ? 180 : 440;
      o.type = type === 'clear' ? 'triangle' : 'sine'; o.frequency.value = freq;
      g.gain.setValueAtTime(.0001, ctx.currentTime); g.gain.exponentialRampToValueAtTime(.06, ctx.currentTime + .02); g.gain.exponentialRampToValueAtTime(.0001, ctx.currentTime + .22);
      o.connect(g); g.connect(ctx.destination); o.start(); o.stop(ctx.currentTime + .24);
    } catch(e) {}
  }

  const norm = cells => { const minX = Math.min(...cells.map(c => c[0])), minY = Math.min(...cells.map(c => c[1])); return cells.map(([x,y]) => [x-minX,y-minY]).sort((a,b)=>a[1]-b[1]||a[0]-b[0]); };
  const rotate = cells => norm(cells.map(([x,y]) => [y, -x]));
  const mirror = cells => norm(cells.map(([x,y]) => [-x, y]));
  const same = (a,b) => JSON.stringify(a) === JSON.stringify(b);
  function variants(shape) { let out = [], cur = norm(shape); for (let m=0;m<2;m++){ for(let r=0;r<4;r++){ if(!out.some(v=>same(v,cur))) out.push(cur); cur=rotate(cur); } cur=mirror(shape); } return out; }

  function difficulty(){ return state.score < 250 ? 'low' : state.score < 700 ? 'mid' : 'high'; }

  // ----- SMART RECOVERY SYSTEM -----
  function assessDanger(){
    let freeCells = state.board.filter(c => !c).length;
    let filledCells = 64 - freeCells;
    if(freeCells <= 6) return 'critical';
    if(freeCells <= 14) return 'high';
    if(freeCells <= 25) return 'medium';
    return 'low';
  }

  function countFragmentedHoles(){
    let holes = 0;
    let visited = new Set();
    for(let i=0;i<64;i++){
      if(!state.board[i] && !visited.has(i)){
        holes++;
        let queue = [i];
        visited.add(i);
        while(queue.length){
          let idx = queue.shift();
          let neighbors = [idx-1, idx+1, idx-8, idx+8].filter(n => n>=0 && n<64);
          for(let n of neighbors){
            if(!state.board[n] && !visited.has(n)){
              visited.add(n);
              queue.push(n);
            }
          }
        }
      }
    }
    return holes;
  }

  function weightedBlock(){
    const level = difficulty();
    const danger = assessDanger();
    const holes = countFragmentedHoles();

    let pool = BLOCKS.filter(b => state.score >= b.min).map(b => ({...b, w: level === 'high' ? (b.high || b.base) : b.base}));

    // Smart recovery: boost useful pieces when danger is high
    const recoveryBoost = danger === 'critical' ? 4.0 : danger === 'high' ? 2.2 : danger === 'medium' ? 1.3 : 1.0;

    if(danger !== 'low'){
      pool = pool.map(b => ({
        ...b,
        w: b.recovery ? b.w * recoveryBoost : b.w * 0.6
      }));
    }

    // If many fragmented holes, prefer singles and small pieces
    if(holes >= 6){
      pool = pool.map(b => {
        if(b.cells.length <= 2) return {...b, w: b.w * 1.6};
        if(b.cells.length <= 4) return {...b, w: b.w * 1.1};
        return {...b, w: b.w * 0.4};
      });
    }

    // If very open board, prefer larger pieces
    if(danger === 'low' && holes <= 2){
      pool = pool.filter(b => !b.recovery || Math.random() > 0.7);
    }

    if(level === 'low') pool = pool.map(b => ({...b, w: ['rare','hard','heavy'].includes(b.tier) ? b.w*.25 : b.w}));
    if(level === 'mid') pool = pool.map(b => ({...b, w: b.tier === 'hard' ? b.w*.65 : b.w}));

    if(Math.random() < .22) pool = pool.map(b => ({...b, w: b.recovery ? b.w*2.2 : b.w}));
    if(state.pieces.some(p => p.name === 'square3')) pool = pool.map(b => b.name === 'square3' ? {...b,w:0} : b);

    const total = pool.reduce((s,b)=>s+b.w,0);
    let r = Math.random()*total;
    return pool.find(b => (r -= b.w) <= 0) || pool[0];
  }

  function randPiece() {
    const block = weightedBlock();
    const vars = variants(block.cells);
    return {
      id: crypto.randomUUID ? crypto.randomUUID() : String(Math.random()),
      name: block.name,
      impact: !!block.impact,
      cells: vars[Math.floor(Math.random()*vars.length)],
      color: COLORS[Math.floor(Math.random()*COLORS.length)],
      used: false
    };
  }

  function newPieces() {
    state.pieces = [randPiece(), randPiece(), randPiece()];
    if(!state.pieces.some(p => canPlace(p,0)) && Math.random()<.7) state.pieces[2] = randPieceFromRecovery();
    state.selected = null;
    state.combo = 1;
    state.blocksUsedThisRound = 0;
    renderPieces();
  }

  function randPieceFromRecovery(){
    const block = BLOCKS.filter(b=>b.recovery)[Math.floor(Math.random()*BLOCKS.filter(b=>b.recovery).length)];
    const vars=variants(block.cells);
    return {id:String(Math.random()),name:block.name,cells:vars[Math.floor(Math.random()*vars.length)],color:COLORS[Math.floor(Math.random()*COLORS.length)],used:false};
  }

  // ----- END SMART RECOVERY -----

  function boardCellSize(){ return els.board.children[0]?.getBoundingClientRect().width || 40; }

  function renderBoard() {
    els.board.innerHTML = '';
    state.board.forEach((cell, i) => {
      const d = document.createElement('div'); d.className = 'cell' + (cell ? ' filled' : ''); d.dataset.i = i;
      if (cell) { d.style.setProperty('--c1', cell[0]); d.style.setProperty('--c2', cell[1]); }
      d.addEventListener('pointerenter', () => preview(i)); d.addEventListener('pointerleave', clearPreview); d.addEventListener('click', () => state.selected !== null && placeAt(i));
      els.board.appendChild(d);
    });
  }

  function pieceBounds(cells){ return {w: Math.max(...cells.map(c=>c[0]))+1, h: Math.max(...cells.map(c=>c[1]))+1}; }
  function renderPieces() {
    els.tray.innerHTML = '';
    state.pieces.forEach((p, idx) => {
      const b = pieceBounds(p.cells), card = document.createElement('div');
      card.className = 'piece-card' + (p.used ? ' used' : '') + (state.selected === idx ? ' selected' : '');
      card.draggable = !p.used;
      const pieceCellSize = window.innerWidth <= 720 ? 20 : 26;
      const grid = document.createElement('div');
      grid.className = 'piece-grid';
      grid.style.gridTemplateColumns = `repeat(${b.w},${pieceCellSize}px)`;
      grid.style.gridTemplateRows = `repeat(${b.h},${pieceCellSize}px)`;
      for(let y=0;y<b.h;y++) for(let x=0;x<b.w;x++){
        const c=document.createElement('div');
        if(p.cells.some(([cx,cy])=>cx===x&&cy===y)){
          c.className='mini-cell';
          c.style.setProperty('--c1',p.color[0]);
          c.style.setProperty('--c2',p.color[1]);
        } else {
          c.style.visibility='hidden';
          c.style.pointerEvents='none';
        }
        grid.appendChild(c);
      }
      card.appendChild(grid);
      card.addEventListener('pointerdown',e=>startPointerDrag(e,idx,card));
      els.tray.appendChild(card);
    });
  }

  function startPointerDrag(e, idx, card){
    if(state.pieces[idx]?.used || e.button > 0) return;
    const hitMiniCell = e.target?.closest?.('.mini-cell');
    if(!hitMiniCell || !card.contains(hitMiniCell)) return;
    e.preventDefault();
    // Do not re-render here; re-rendering would recreate tray nodes and break live drag.
    state.selected = idx;
    card.setPointerCapture?.(e.pointerId);
    const originalParent = card.parentNode;
    const originalNext = card.nextSibling;
    const cardRect = card.getBoundingClientRect();
    const offsetX = e.clientX - (cardRect.left + cardRect.width / 2);
    const offsetY = e.clientY - (cardRect.top + cardRect.height / 2);
    const placeholder = document.createElement('div');
    placeholder.style.width = cardRect.width + 'px';
    placeholder.style.height = cardRect.height + 'px';
    placeholder.style.pointerEvents = 'none';
    originalParent.insertBefore(placeholder, card);
    document.body.appendChild(card);

    const originalCardStyle = card.getAttribute('style') || '';
    const gridEl = card.querySelector('.piece-grid');
    const originalGridStyle = gridEl ? (gridEl.getAttribute('style') || '') : '';
    const miniCells = Array.from(card.querySelectorAll('.mini-cell'));
    const originalMiniStyles = miniCells.map(el => el.getAttribute('style') || '');

    card.style.position = 'fixed';
    card.style.left = '0px';
    card.style.top = '0px';
    card.style.margin = '0';
    card.style.zIndex = '1000';
    card.style.pointerEvents = 'none';
    card.style.background = 'transparent';
    card.style.border = '0';
    card.style.boxShadow = 'none';
    // Keep shadow accurate to block shape (not the whole card rectangle).
    card.style.filter = 'drop-shadow(0 8px 14px rgba(0,0,0,.32))';
    card.style.opacity = '1';
    card.style.transition = 'none';
    card.style.visibility = 'hidden';
    syncGhostToBoard(card, idx);
    state.dragGhost = card;
    // Position immediately to avoid the brief jump from top-left.
    moveGhost(e.clientX - offsetX, e.clientY - offsetY);
    // Reveal only after first positioned transform is applied.
    requestAnimationFrame(() => { card.style.visibility = ''; });
    let rafId = 0;
    const dragPos = {x: e.clientX, y: e.clientY};
    const renderDrag = () => {
      rafId = 0;
      moveGhost(dragPos.x - offsetX, dragPos.y - offsetY);
    };
    const queueRender = () => {
      if (!rafId) rafId = requestAnimationFrame(renderDrag);
    };
    queueRender();
    const move = ev => {
      dragPos.x = ev.clientX;
      dragPos.y = ev.clientY;
      queueRender();
      const cell = cellFromPieceAnchor(ev.clientX, ev.clientY);
      if(cell) preview(+cell.dataset.i); else clearPreview();
    };
    const end = ev => {
      document.removeEventListener('pointermove', move);
      document.removeEventListener('pointerup', end);
      document.removeEventListener('pointercancel', end);
      const cell = cellFromPieceAnchor(ev.clientX, ev.clientY);
      const finishCleanup = () => {
        if (rafId) cancelAnimationFrame(rafId);
        // Hide while restoring/reinserting to prevent any top-left flash.
        card.style.visibility = 'hidden';
        if (originalParent) {
          if (originalNext && originalNext.parentNode === originalParent) {
            originalParent.insertBefore(card, originalNext);
          } else {
            originalParent.appendChild(card);
          }
        }
        card.setAttribute('style', originalCardStyle);
        if (gridEl) gridEl.setAttribute('style', originalGridStyle);
        miniCells.forEach((el, i) => el.setAttribute('style', originalMiniStyles[i] || ''));
        // Reveal only after DOM/styling has settled.
        requestAnimationFrame(() => { if (card.isConnected) card.style.visibility = ''; });
        placeholder.remove();
        state.dragGhost = null;
      };
      if(cell && state.selected !== null && canPlace(state.pieces[state.selected], +cell.dataset.i)) {
        finishCleanup();
        placeAt(+cell.dataset.i);
      } else {
        // Cleanup immediately to avoid any body-level top-left flash on invalid return.
        finishCleanup();
        card.classList.add('invalid');
        setTimeout(()=>card.classList.remove('invalid'),260);
        beep('over');
        clearPreview();
      }
    };
    document.addEventListener('pointermove', move, {passive:true});
    document.addEventListener('pointerup', end, {once:true});
    document.addEventListener('pointercancel', end, {once:true});
  }

  function syncGhostToBoard(ghost, idx){
    const p = state.pieces[idx], b = pieceBounds(p.cells), s = Math.max(30, Math.min(boardCellSize(), 58));
    ghost.style.width = (b.w*s+6) + 'px'; ghost.style.minHeight = (b.h*s+6) + 'px'; ghost.style.padding = '0'; ghost.style.background = 'transparent'; ghost.style.border = '0';
    const grid = ghost.querySelector('.piece-grid');
    if(grid){ grid.style.gridTemplateColumns=`repeat(${b.w},${s}px)`; grid.style.gridTemplateRows=`repeat(${b.h},${s}px)`; }
    ghost.querySelectorAll('.mini-cell').forEach(c=>{ c.style.width=s+'px'; c.style.height=s+'px'; c.style.margin='0'; });
  }

  function moveGhost(x,y){ if(!state.dragGhost) return; state.dragGhost.style.transform = `translate3d(${x}px, ${y}px, 0) translate(-50%, -50%) scale(1.08)`; }

  function dropCellFromPoint(x,y){
    const ghost = state.dragGhost;
    if(ghost) ghost.style.display = 'none';
    const cell = document.elementFromPoint(x,y)?.closest?.('.cell');
    if(ghost) ghost.style.display = '';
    return cell;
  }

  function cellFromPieceAnchor(x,y){
    if(state.selected === null) return null;
    const p = state.pieces[state.selected];
    const minX = Math.min(...p.cells.map(c=>c[0])), minY = Math.min(...p.cells.map(c=>c[1]));
    const size = boardCellSize();
    return dropCellFromPoint(x - (minX + .5) * size, y - (minY + .5) * size);
  }

  function selectPiece(idx){ if(state.pieces[idx]?.used) return; state.selected = idx; renderPieces(); }
  function canPlace(piece, index){ const x0=index%SIZE,y0=Math.floor(index/SIZE); return piece.cells.every(([x,y])=>{ const nx=x0+x,ny=y0+y; return nx>=0&&nx<SIZE&&ny>=0&&ny<SIZE&&!state.board[ny*SIZE+nx]; }); }
  function cellsFor(piece,index){ const x0=index%SIZE,y0=Math.floor(index/SIZE); return piece.cells.map(([x,y])=>{ const nx=x0+x,ny=y0+y; return nx>=0&&nx<SIZE&&ny>=0&&ny<SIZE ? ny*SIZE+nx : -1; }); }
  function clearPreview(){
    lastPreviewCells.forEach(i => els.board.children[i]?.classList.remove('ghost','bad'));
    lastScoreLineCells.forEach(i => els.board.children[i]?.classList.remove('score-line'));
    lastPreviewCells = [];
    lastScoreLineCells = [];
    lastPreviewKey = '';
  }
  function preview(index){
    if(state.selected===null) { clearPreview(); return; }
    const p=state.pieces[state.selected], ok=canPlace(p,index), placed=cellsFor(p,index);
    const validPlaced = placed.filter(i => i >= 0);
    const key = `${state.selected}|${index}|${ok?'1':'0'}|${validPlaced.join(',')}`;
    if (key === lastPreviewKey) return;

    lastPreviewCells.forEach(i => els.board.children[i]?.classList.remove('ghost','bad'));
    lastScoreLineCells.forEach(i => els.board.children[i]?.classList.remove('score-line'));
    lastPreviewCells = validPlaced;
    lastScoreLineCells = [];
    lastPreviewKey = key;

    validPlaced.forEach(i=>{ if(els.board.children[i]) els.board.children[i].classList.add(ok?'ghost':'bad'); });
    if(ok) scorePreview(validPlaced);
  }

  function scorePreview(placed){
    const temp = [...state.board]; placed.forEach(i=>{ if(i>=0) temp[i]=['x','x']; });
    let hit = false;
    const scoreCells = [];
    for(let y=0;y<SIZE;y++) if([...Array(SIZE)].every((_,x)=>temp[y*SIZE+x])) { hit = true; for(let x=0;x<SIZE;x++) scoreCells.push(y*SIZE+x); }
    for(let x=0;x<SIZE;x++) if([...Array(SIZE)].every((_,y)=>temp[y*SIZE+x])) { hit = true; for(let y=0;y<SIZE;y++) scoreCells.push(y*SIZE+x); }
    if(hit) {
      lastScoreLineCells = [...new Set(scoreCells)];
      lastScoreLineCells.forEach(i => els.board.children[i]?.classList.add('score-line'));
    }
  }

  function placeAt(index){
    if(state.selected===null||state.over) return;
    const p=state.pieces[state.selected];
    if(!canPlace(p,index)){ beep('over'); return; }
    const target=cellsFor(p,index);
    target.forEach(i=>state.board[i]=p.color);
    p.used=true;
    state.cellsPlaced += p.cells.length;
    state.score += (p.cells.length * 10) + (p.impact ? 50 : 0);
    state.events++;
    state.blocksUsedThisRound++;
    beep(p.impact?'combo':'place');
    renderBoard();
    burstForCells(target,p.impact?'#ffe45c':p.color[0]);
    if(p.impact) showFloat('POWER +50');
    clearLines();
    if(state.pieces.every(x=>x.used)) newPieces(); else { state.selected=null; renderPieces(); }
    updateUI(); saveProgress(); setTimeout(checkGameOver, 560);
  }

  function clearLines(){
    const lines=[];
    for(let y=0;y<SIZE;y++) if([...Array(SIZE)].every((_,x)=>state.board[y*SIZE+x])) lines.push([...Array(SIZE)].map((_,x)=>y*SIZE+x));
    for(let x=0;x<SIZE;x++) if([...Array(SIZE)].every((_,y)=>state.board[y*SIZE+x])) lines.push([...Array(SIZE)].map((_,y)=>y*SIZE+x));
    const unique=[...new Set(lines.flat())];
    if(!unique.length){ return; }
    unique.forEach(i=>els.board.children[i]?.classList.add('clear'));
    const count=lines.length, bonus=[0,100,250,500,1000][count] || Math.min(5000,2000+(count-5)*750);
    state.combo=Math.min(5,state.combo+1); state.maxCombo=Math.max(state.maxCombo,state.combo);
    state.score += bonus*state.combo; state.lines += count;
    if (state.combo >= 3) {
      showFloat('PALONG PALOOO!');
    } else if (state.combo === 2) {
      showFloat('PALOO!');
    } else {
      showFloat('ANG LUPET!');
    }
    beep(count>=2?'combo':'clear');
    setTimeout(()=>{unique.forEach(i=>state.board[i]=null); renderBoard(); if(state.board.every(v=>!v)) greatClear();},420);
  }
  function greatClear(){
    state.score += 3000;
    showFloat('MAY BITAW +3,000');
    document.body.classList.add('great-flash');
    burstForCells([...Array(64)].map((_,i)=>i),'#ffe45c');
    beep('combo');
    setTimeout(()=>document.body.classList.remove('great-flash'),700);
    updateUI();
  }
  function anyFit(){ return state.pieces.filter(p=>!p.used).some(p=>{ for(let i=0;i<64;i++) if(canPlace(p,i)) return true; return false; }); }
  async function checkGameOver(){ if(anyFit()) return; state.over=true; document.body.classList.add('game-over-state'); markBlockedHints(); beep('over'); await saveScore(); els.final.textContent=state.score.toLocaleString(); els.modalBest.textContent=state.best.toLocaleString(); els.modal.classList.add('show'); loadLeaderboard(); }
  function markBlockedHints(){ document.querySelectorAll('.cell:not(.filled)').forEach((c,i)=>{ if(state.pieces.filter(p=>!p.used).every(p=>!canPlace(p,i))) c.classList.add('bad'); }); }
  function updateUI(){ state.best=Math.max(state.best,state.score); els.score.textContent=state.score.toLocaleString(); els.best.textContent=state.best.toLocaleString(); els.combo.textContent='x'+state.combo; els.lines.textContent=state.lines; }
  function showFloat(t){ els.float.textContent=t; els.float.classList.remove('show'); void els.float.offsetWidth; els.float.classList.add('show'); }
  function burstForCells(cells,color){ cells.slice(0,10).forEach(i=>{ const r=els.board.children[i].getBoundingClientRect(); for(let n=0;n<5;n++){ const s=document.createElement('i'); s.className='spark'; s.style.left=(r.left+r.width/2)+'px'; s.style.top=(r.top+r.height/2)+'px'; s.style.color=color; s.style.setProperty('--x',(Math.random()*120-60)+'px'); s.style.setProperty('--y',(Math.random()*120-60)+'px'); els.particles.appendChild(s); setTimeout(()=>s.remove(),750); }}); }
  function serialize(){ return { board:state.board,pieces:state.pieces,score:state.score,combo:state.combo,linesCleared:state.lines,startedAt:state.startedAt }; }
  let saveTimer=null; function saveProgress(){ clearTimeout(saveTimer); els.save.textContent='Saving...'; saveTimer=setTimeout(()=>api('save_progress.php',{progress:serialize()}).then(()=>els.save.textContent='Synced').catch(()=>els.save.textContent='Offline'),550); }

  async function saveScore(){
    const playtime=Math.floor((Date.now()-state.startedAt)/1000);
    try{
      els.save.textContent = 'Saving score...';
      const res=await api('save_score.php',{
        score: state.score,
        highest_combo: state.maxCombo,
        total_lines_cleared: state.lines,
        cells_placed: state.cellsPlaced,
        playtime_seconds: playtime,
        events: state.events
      });
      console.log('saveScore response:', res);
      els.save.textContent = res && res.success ? 'Score saved!' : 'Save failed: ' + (res ? res.message : 'no response');
      if(res && res.success) {
        if(res.rank) { els.modalRank.textContent='#'+res.rank; els.rank.textContent='Rank #'+res.rank; }
      } else {
        console.warn('saveScore not successful:', res ? JSON.stringify(res) : 'empty response');
        // Show an alert so user can see the error
        if(res && res.message) alert('Score save error: ' + res.message);
      }
      loadLeaderboard();
    }catch(e){
      console.error('saveScore error:', e);
      els.save.textContent = 'Save error';
      loadLeaderboard();
    }
  }

  async function loadStats(){ try{ const res=await api('get_player_stats.php'); if(res.success){ state.best=parseInt(res.stats.best_score||0,10); if(res.progress && confirm('Resume your saved Block Game run?')) restore(res.progress); updateUI(); if(res.rank) els.rank.textContent='Rank #'+res.rank; } }catch(e){} }
  function restore(p){ state.board=Array.isArray(p.board)&&p.board.length===64?p.board:state.board; state.pieces=Array.isArray(p.pieces)&&p.pieces.length?p.pieces:state.pieces; state.score=+p.score||0; state.combo=+p.combo||1; state.lines=+p.linesCleared||0; state.startedAt=+p.startedAt||Date.now(); renderBoard(); renderPieces(); }

  async function loadLeaderboard(){
    try{
      const res = await api('get_leaderboard.php?limit=10&_=' + Date.now());
      if(!res || !res.success){
        console.error('Leaderboard API returned error', res);
        els.leaders.classList.remove('skeleton');
        els.leaders.innerHTML = '<p class="hint">Leaderboard unavailable.</p>';
        return;
      }
      els.leaders.classList.remove('skeleton');
      els.leaders.innerHTML = (res.leaders.length ? res.leaders.map(r => {
        let avatar = r.profile_image || 'default-avatar.png';
        if (!avatar.match(/^https?:\/\//)) avatar = '../../public/uploads/avatars/' + avatar;
        return `<div class="leader-row top${r.rank<=3?r.rank:''}${r.is_current_user?' current-player':''}"><div class="rank">${r.rank===1?'👑':r.rank===2?'🥈':r.rank===3?'🥉':'#'+r.rank}</div><img class="leader-avatar" src="${escapeHtml(avatar)}" onerror="this.src='../../public/images/default-avatar.png'" alt="" /><div class="leader-name"><strong>${escapeHtml(r.full_name||r.username)}</strong><small>${r.highest_combo} combo • ${r.total_games} games</small></div><div class="leader-score">${Number(r.score).toLocaleString()}</div></div>`;
      }).join('') : '<p class="hint">Be the first player on the leaderboard.</p>');
      if(res.player_rank) els.rank.textContent = 'Rank #'+res.player_rank;
    }catch(e){
      console.error('Failed loading leaderboard', e);
      els.leaders.classList.remove('skeleton');
      els.leaders.innerHTML = '<p class="hint">Unable to load leaderboard.</p>';
    }
  }

  function escapeHtml(s){ return String(s).replace(/[&<>'"]/g,m=>({'&':'&','<':'<','>':'>',"'":'&#039;','"':'"'}[m])); }
  function setTheme(theme){ document.body.classList.toggle('light-mode', theme === 'light'); localStorage.setItem('blockGameTheme', theme); $('themeBtn').innerHTML = theme === 'light' ? '<i class="fas fa-moon"></i> Dark Mode' : '<i class="fas fa-sun"></i> Light Mode'; }
  function openLeaderboard(){ document.querySelector('.leaderboard')?.scrollIntoView({behavior:'smooth',block:'center'}); }
  function newGame(){ document.body.classList.remove('game-over-state'); Object.assign(state,{board:Array(64).fill(null),pieces:[],selected:null,score:0,combo:1,maxCombo:1,lines:0,cellsPlaced:0,events:0,startedAt:Date.now(),over:false,blocksUsedThisRound:0}); els.modal.classList.remove('show'); newPieces(); renderBoard(); updateUI(); }

  // Native HTML5 drag/drop removed in favor of true pointer hold-and-drag.
  $('newGameBtn').onclick=()=>confirm('Start a new Block Game?')&&newGame(); $('retryBtn').onclick=newGame; $('closeGameOverBtn').onclick=()=>els.modal.classList.remove('show'); $('leaderboardBtn').onclick=openLeaderboard; $('modalLeaderboardBtn').onclick=()=>{els.modal.classList.remove('show'); openLeaderboard();}; $('themeBtn').onclick=()=>setTheme(document.body.classList.contains('light-mode')?'dark':'light'); $('soundBtn').onclick=()=>{state.muted=!state.muted; $('soundBtn').innerHTML=state.muted?'<i class="fas fa-volume-xmark"></i>':'<i class="fas fa-volume-high"></i>';};
  setInterval(loadLeaderboard, 30000);
  setTheme(localStorage.getItem('blockGameTheme') || 'dark'); newGame(); loadStats(); loadLeaderboard();
})();