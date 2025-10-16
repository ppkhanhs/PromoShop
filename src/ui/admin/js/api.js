export async function api(path, options = {}) {
  const opts = { ...options };

  if (opts.body && typeof opts.body !== "string") {
    opts.headers = {
      "Content-Type": "application/json",
      ...(opts.headers || {}),
    };
    opts.body = JSON.stringify(opts.body);
  }

  const response = await fetch(path, opts);

  if (!response.ok) {
    let message = `Yêu cầu thất bại (${response.status})`;
    try {
      const data = await response.json();
      if (data?.error) message = data.error;
      if (data?.msg) message = data.msg;
    } catch (err) {
      const text = await response.text();
      if (text) message = text;
    }
    throw new Error(message);
  }

  const contentType = response.headers.get("content-type") || "";
  if (!contentType.includes("application/json")) {
    return null;
  }

  return response.json();
}
