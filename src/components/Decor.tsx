import { useMemo } from "react";

export function Dust() {
  const particles = useMemo(
    () =>
      Array.from({ length: 64 }, () => {
        const s = Math.random() * 2.5 + 1;
        return {
          width: s,
          height: s,
          left: `${Math.random() * 100}%`,
          bottom: `${Math.random() * 25}%`,
          animationDuration: `${9 + Math.random() * 16}s`,
          animationDelay: `${Math.random() * 14}s`,
        };
      }),
    [],
  );
  return (
    <div className="dust" aria-hidden>
      {particles.map((style, i) => (
        <div key={i} className="dust-p" style={style} />
      ))}
    </div>
  );
}

export function Candle() {
  return (
    <div className="candle" aria-hidden>
      <div className="candle-glow" />
      <div className="candle-flame" />
      <div className="candle-body" />
      <div className="candle-base" />
    </div>
  );
}

export function HerbPot() {
  return (
    <div className="herb-pot" aria-hidden>
      <div className="herb-leaves">
        <div className="herb-leaf" />
        <div className="herb-leaf" />
        <div className="herb-leaf" />
        <div className="herb-leaf" />
      </div>
      <div className="herb-pot-rim" />
      <div className="herb-pot-body" />
    </div>
  );
}

export function Titre() {
  return (
    <>
      <h1 className="app-title">Ma Cuisine</h1>
      <p className="app-subtitle">— Recettes de famille —</p>
    </>
  );
}
