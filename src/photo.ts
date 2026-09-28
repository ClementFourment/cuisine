const COTE_MAX = 1200;
const POIDS_CIBLE = 900 * 1024;

function toBlob(canvas: HTMLCanvasElement, type: string, quality: number): Promise<Blob | null> {
  return new Promise((resolve) => canvas.toBlob(resolve, type, quality));
}

/**
 * Redimensionne et compresse une photo dans le navigateur avant l'envoi
 * (1200 px maximum, WebP ou JPEG), pour rester sous la limite de 1 Mo du serveur.
 */
export async function compresserPhoto(file: File): Promise<Blob> {
  let bitmap: ImageBitmap;
  try {
    bitmap = await createImageBitmap(file, { imageOrientation: "from-image" });
  } catch {
    throw new Error("Impossible de lire cette image. Essayez une photo JPEG ou PNG.");
  }
  const scale = Math.min(1, COTE_MAX / Math.max(bitmap.width, bitmap.height));
  const canvas = document.createElement("canvas");
  canvas.width = Math.round(bitmap.width * scale);
  canvas.height = Math.round(bitmap.height * scale);
  canvas.getContext("2d")!.drawImage(bitmap, 0, 0, canvas.width, canvas.height);
  bitmap.close();

  for (const quality of [0.82, 0.7, 0.55, 0.4]) {
    let blob = await toBlob(canvas, "image/webp", quality);
    // Les vieux Safari ne savent pas encoder en WebP : ils renvoient du PNG.
    if (!blob || blob.type !== "image/webp") blob = await toBlob(canvas, "image/jpeg", quality);
    if (blob && blob.size <= POIDS_CIBLE) return blob;
  }
  throw new Error("Photo trop lourde, même compressée.");
}
