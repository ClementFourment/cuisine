import type { Recette, RecetteInput, User } from "../shared/types";

export class ApiError extends Error {
  constructor(
    public status: number,
    message: string,
  ) {
    super(message);
  }
}

async function request<T>(method: string, url: string, body?: unknown): Promise<T> {
  const init: RequestInit = { method, headers: {} };
  if (body instanceof Blob) {
    init.body = body;
    init.headers = { "Content-Type": body.type };
  } else if (body !== undefined) {
    init.body = JSON.stringify(body);
    init.headers = { "Content-Type": "application/json" };
  }
  const res = await fetch(url, init);
  const data = await res.json().catch(() => ({}));
  if (!res.ok) {
    const message =
      res.status === 401 && url !== "/api/login"
        ? "Session expirée : reconnectez-vous."
        : (data.error ?? "Erreur réseau, réessayez.");
    throw new ApiError(res.status, message);
  }
  return data as T;
}

type R = { recette: Recette };

export const api = {
  me: () => request<{ user: User | null }>("GET", "/api/me").then((d) => d.user),
  login: (login: string, password: string) =>
    request<{ user: User }>("POST", "/api/login", { login, password }).then((d) => d.user),
  logout: () => request("POST", "/api/logout"),

  recettes: () => request<{ recettes: Recette[] }>("GET", "/api/recettes").then((d) => d.recettes),
  create: (r: RecetteInput) => request<R>("POST", "/api/recettes", r).then((d) => d.recette),
  update: (id: number, r: RecetteInput) => request<R>("PUT", `/api/recettes/${id}`, r).then((d) => d.recette),
  remove: (id: number) => request("DELETE", `/api/recettes/${id}`),
  setPhoto: (id: number, photo: Blob) => request<R>("PUT", `/api/recettes/${id}/photo`, photo).then((d) => d.recette),
  removePhoto: (id: number) => request<R>("DELETE", `/api/recettes/${id}/photo`).then((d) => d.recette),
};
