import type { TypeRecette } from "../../shared/types";
import { Candle, HerbPot } from "./Decor";

export const LIVRES: { type: TypeRecette; label: string; className: string; ornement: string; lignes: number }[] = [
  { type: "apero", label: "Apéros", className: "book-aperos", ornement: "✦", lignes: 2 },
  { type: "entree", label: "Entrées", className: "book-entrees", ornement: "❧", lignes: 1 },
  { type: "plat", label: "Plats", className: "book-plats", ornement: "◆", lignes: 1 },
  { type: "dessert", label: "Desserts", className: "book-desserts", ornement: "✿", lignes: 0 },
];

interface Props {
  onOpen: (type: TypeRecette, bookEl: HTMLElement) => void;
}

export function Shelf({ onOpen }: Props) {
  return (
    <div className="shelf-container">
      <div className="shelf-deco left">
        <Candle />
      </div>
      <div className="books-row">
        {LIVRES.map((l) => {
          const lines =
            l.lignes > 0 ? (
              <div className="book-lines">
                {Array.from({ length: l.lignes }, (_, i) => (
                  <div key={i} className="book-line" />
                ))}
              </div>
            ) : null;
          return (
            <button
              key={l.type}
              type="button"
              className={`book ${l.className}`}
              aria-label={`Ouvrir le livre ${l.label}`}
              onClick={(e) => onOpen(l.type, e.currentTarget)}
            >
              <div className="book-spine">
                <div className="book-ornament top">{l.ornement}</div>
                {lines}
                <div className="book-label">{l.label}</div>
                {lines}
                <div className="book-ornament bot">{l.ornement}</div>
              </div>
            </button>
          );
        })}
      </div>
      <div className="shelf-deco right">
        <HerbPot />
      </div>
      <div className="shelf-board">
        <div className="shelf-shadow" />
        <div className="shelf-support" />
      </div>
    </div>
  );
}
