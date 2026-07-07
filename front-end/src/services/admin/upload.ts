import api from "../api";

export function uploadImage(file: File) {
  const formData = new FormData();
  formData.append("image", file);

  return api
    .post<{ url: string }>("/admin/uploads", formData, {
      headers: { "Content-Type": "multipart/form-data" },
    })
    .then((r) => r.data.url);
}
