(() => {
  const initFluidBackground = () => {
    const root = document.body;
    const canvas = document.createElement('canvas');
    canvas.className = 'fluid-canvas';
    root.appendChild(canvas);

    const gl = canvas.getContext('webgl', { alpha: true, antialias: true });
    if (!gl) return;

    const vShaderSrc = `
      attribute vec2 aPos;
      varying vec2 vUv;
      void main() {
        vUv = (aPos + 1.0) * 0.5;
        gl_Position = vec4(aPos, 0.0, 1.0);
      }
    `;

    const fShaderSrc = `
      precision mediump float;
      varying vec2 vUv;
      uniform vec2 uRes;
      uniform float uTime;
      uniform vec2 uMouse;

      float hash(vec2 p){
        return fract(sin(dot(p, vec2(127.1, 311.7))) * 43758.5453123);
      }

      float noise(vec2 p){
        vec2 i = floor(p);
        vec2 f = fract(p);
        vec2 u = f*f*(3.0-2.0*f);
        return mix(mix(hash(i + vec2(0.0,0.0)), hash(i + vec2(1.0,0.0)), u.x),
                   mix(hash(i + vec2(0.0,1.0)), hash(i + vec2(1.0,1.0)), u.x), u.y);
      }

      float fbm(vec2 p){
        float v = 0.0;
        float a = 0.5;
        for(int i=0;i<5;i++){
          v += a * noise(p);
          p *= 2.0;
          a *= 0.52;
        }
        return v;
      }

      float blob(vec2 uv, vec2 p, float r){
        return smoothstep(r, r - 0.18, distance(uv, p));
      }

      void main() {
        vec2 uv = vUv;
        vec2 m = uMouse;

        float t = uTime * 0.22;
        vec2 d = uv - m;
        float r = length(d) + 0.0001;
        float ang = 1.15 * exp(-r * 7.5);
        mat2 rot = mat2(cos(ang), -sin(ang), sin(ang), cos(ang));
        uv = m + rot * d;

        vec2 flow = vec2(
          fbm(uv * 3.8 + vec2(0.0, t)) - 0.5,
          fbm(uv * 3.8 + vec2(4.0, -t)) - 0.5
        );
        uv += flow * 0.12;

        float c1 = blob(uv, m + flow * 0.045, 0.21);
        float c2 = blob(uv, m + vec2(0.08 * sin(t * 1.9), 0.07 * cos(t * 1.6)) - flow * 0.04, 0.15);
        float c3 = blob(uv, vec2(0.5 + 0.18 * sin(t * 0.45), 0.5 + 0.14 * cos(t * 0.5)), 0.12);

        float turbulence = fbm(uv * 6.0 - t * 0.7) * 0.35;
        vec3 base = vec3(0.05, 0.01, 0.02);
        vec3 fluid = vec3(1.0, 0.18, 0.35) * c1 + vec3(0.92, 0.28, 0.5) * c2 + vec3(0.4, 0.1, 0.2) * c3;
        vec3 col = base + fluid * (0.72 + turbulence * 0.85);

        float vignette = smoothstep(1.1, 0.2, distance(uv, vec2(0.5)));
        col *= vignette;

        gl_FragColor = vec4(col, 0.7);
      }
    `;

    const compile = (type, src) => {
      const s = gl.createShader(type);
      gl.shaderSource(s, src);
      gl.compileShader(s);
      return s;
    };

    const program = gl.createProgram();
    gl.attachShader(program, compile(gl.VERTEX_SHADER, vShaderSrc));
    gl.attachShader(program, compile(gl.FRAGMENT_SHADER, fShaderSrc));
    gl.linkProgram(program);
    gl.useProgram(program);

    const buffer = gl.createBuffer();
    gl.bindBuffer(gl.ARRAY_BUFFER, buffer);
    gl.bufferData(gl.ARRAY_BUFFER, new Float32Array([
      -1, -1,  1, -1, -1,  1,
      -1,  1,  1, -1,  1,  1
    ]), gl.STATIC_DRAW);

    const aPos = gl.getAttribLocation(program, 'aPos');
    gl.enableVertexAttribArray(aPos);
    gl.vertexAttribPointer(aPos, 2, gl.FLOAT, false, 0, 0);

    const uRes = gl.getUniformLocation(program, 'uRes');
    const uTime = gl.getUniformLocation(program, 'uTime');
    const uMouse = gl.getUniformLocation(program, 'uMouse');

    const mouse = { x: 0.5, y: 0.5, tx: 0.5, ty: 0.5, lastMove: Date.now(), seed: Math.random() * 1000 };

    const resize = () => {
      const dpr = Math.min(window.devicePixelRatio || 1, 2);
      canvas.width = Math.floor(window.innerWidth * dpr);
      canvas.height = Math.floor(window.innerHeight * dpr);
      canvas.style.width = `${window.innerWidth}px`;
      canvas.style.height = `${window.innerHeight}px`;
      gl.viewport(0, 0, canvas.width, canvas.height);
      gl.uniform2f(uRes, canvas.width, canvas.height);
    };

    window.addEventListener('resize', resize);
    window.addEventListener('mousemove', (e) => {
      mouse.tx = e.clientX / window.innerWidth;
      mouse.ty = 1 - e.clientY / window.innerHeight;
      mouse.lastMove = Date.now();
    });

    resize();

    const render = (ms) => {
      const t = ms * 0.001;
      if (Date.now() - mouse.lastMove > 20000) {
        const rndA = Math.sin((t * 0.23) + mouse.seed) * 0.5 + 0.5;
        const rndB = Math.cos((t * 0.19) + mouse.seed * 1.7) * 0.5 + 0.5;
        mouse.tx = Math.min(0.92, Math.max(0.08, rndA));
        mouse.ty = Math.min(0.9, Math.max(0.1, rndB));
      }

      mouse.x += (mouse.tx - mouse.x) * 0.08;
      mouse.y += (mouse.ty - mouse.y) * 0.08;

      gl.uniform1f(uTime, t);
      gl.uniform2f(uMouse, mouse.x, mouse.y);
      gl.drawArrays(gl.TRIANGLES, 0, 6);

      requestAnimationFrame(render);
    };

    requestAnimationFrame(render);
  };

  initFluidBackground();

  const revealEls = Array.from(document.querySelectorAll('.reveal'));

  if ('IntersectionObserver' in window && revealEls.length) {
    const observer = new IntersectionObserver((entries) => {
      entries.forEach((entry) => {
        if (entry.isIntersecting) {
          entry.target.classList.add('is-visible');
          observer.unobserve(entry.target);
        }
      });
    }, { threshold: 0.15 });

    revealEls.forEach((el) => observer.observe(el));
  } else {
    revealEls.forEach((el) => el.classList.add('is-visible'));
  }

  const likeBtn = document.getElementById('portfolioLikeBtn');
  const likeCount = document.getElementById('portfolioLikeCount');

  if (likeBtn && likeCount) {
    const likedKey = 'portfolio-liked';
    const countKey = 'portfolio-like-count';
    let liked = localStorage.getItem(likedKey) === '1';
    let count = parseInt(localStorage.getItem(countKey) || '0', 10);

    const renderLike = () => {
      likeBtn.classList.toggle('is-liked', liked);
      likeBtn.setAttribute('aria-pressed', liked ? 'true' : 'false');
      likeBtn.firstChild.textContent = liked ? '❤️ Liked portfolio ' : '🤍 Like this portfolio ';
      likeCount.textContent = String(Math.max(0, count));
    };

    likeBtn.addEventListener('click', () => {
      if (liked) {
        liked = false;
        count = Math.max(0, count - 1);
      } else {
        liked = true;
        count += 1;
      }

      localStorage.setItem(likedKey, liked ? '1' : '0');
      localStorage.setItem(countKey, String(count));
      renderLike();
    });

    renderLike();
  }

  const isTouch = window.matchMedia('(pointer: coarse)').matches;
  if (isTouch) return;

  const dot = document.createElement('div');
  const ring = document.createElement('div');
  dot.className = 'cursor-dot';
  ring.className = 'cursor-ring';
  document.body.append(dot, ring);

  let mouseX = window.innerWidth / 2;
  let mouseY = window.innerHeight / 2;
  let ringX = mouseX;
  let ringY = mouseY;

  const hoverTargets = 'a, button, .btn, .nav-link, .social, .carousel-btn, .nav-toggle, input, label';

  window.addEventListener('mousemove', (e) => {
    mouseX = e.clientX;
    mouseY = e.clientY;
    dot.style.transform = `translate(${mouseX}px, ${mouseY}px)`;
  });

  document.addEventListener('mouseover', (e) => {
    if (e.target.closest(hoverTargets)) {
      ring.classList.add('is-hover');
    }
  });

  document.addEventListener('mouseout', (e) => {
    if (e.target.closest(hoverTargets)) {
      ring.classList.remove('is-hover');
    }
  });

  const animateRing = () => {
    ringX += (mouseX - ringX) * 0.18;
    ringY += (mouseY - ringY) * 0.18;
    ring.style.transform = `translate(${ringX}px, ${ringY}px)`;
    requestAnimationFrame(animateRing);
  };

  animateRing();
})();
