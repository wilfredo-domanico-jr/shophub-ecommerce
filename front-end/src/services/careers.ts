import api from "./api";

export interface JobOpening {
  id: number;
  title: string;
  department: string;
  location: string;
  employment_type: string;
  description: string;
  is_active: boolean;
}

export function getJobOpenings() {
  return api.get<JobOpening[]>("/careers").then((r) => r.data);
}
