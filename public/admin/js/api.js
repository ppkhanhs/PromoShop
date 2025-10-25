import { adminFetch } from "./app.js";

export async function api(path, options = {}) {
  return adminFetch(path, options);
}
