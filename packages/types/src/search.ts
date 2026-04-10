export interface SearchUserResult {
  id: number;
  name: string;
  email: string;
  status: string;
  type: "user";
}

export interface SearchCourseResult {
  id: number;
  title: string;
  status: string;
  short_description: string | null;
  type: "course";
}

export interface SearchResults {
  users: SearchUserResult[];
  courses: SearchCourseResult[];
}
