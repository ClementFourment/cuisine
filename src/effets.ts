let audioCtx: AudioContext | null = null;

/** Petit bruit de page qu'on tourne. */
export function playPageSound() {
  try {
    audioCtx ??= new AudioContext();
    const ctx = audioCtx;
    const len = ctx.sampleRate * 0.07;
    const buf = ctx.createBuffer(1, len, ctx.sampleRate);
    const d = buf.getChannelData(0);
    for (let i = 0; i < len; i++) d[i] = (Math.random() * 2 - 1) * Math.pow(1 - i / len, 2.5) * 0.15;
    const src = ctx.createBufferSource();
    src.buffer = buf;
    const f = ctx.createBiquadFilter();
    f.type = "bandpass";
    f.frequency.value = 3200;
    f.Q.value = 0.4;
    src.connect(f);
    f.connect(ctx.destination);
    src.start();
  } catch {
    // pas de son disponible
  }
}

export function haptic(pattern: number | number[]) {
  try {
    navigator.vibrate?.(pattern);
  } catch {
    // vibration non supportée
  }
}

/** Animation du livre qui s'ouvre (un clone de la tranche pivote puis disparaît). */
export function animateBookOpen(bookEl: HTMLElement, done: () => void) {
  const spine = bookEl.querySelector<HTMLElement>(".book-spine");
  if (!spine || matchMedia("(prefers-reduced-motion: reduce)").matches) return done();
  const rect = spine.getBoundingClientRect();
  const wrap = document.createElement("div");
  wrap.className = "book-clone-wrap";
  wrap.style.cssText = `left:${rect.left}px;top:${rect.top}px;width:${rect.width}px;height:${rect.height}px;`;
  const inner = document.createElement("div");
  inner.className = "book-clone-inner";
  inner.appendChild(spine.cloneNode(true));
  wrap.appendChild(inner);
  document.body.appendChild(wrap);
  bookEl.classList.add("opening");
  setTimeout(() => {
    wrap.remove();
    bookEl.classList.remove("opening");
    done();
  }, 460);
}
