// API envelope
export type { ApiResponse, ApiError, PaginatedResponse, PaginationMeta } from "./api";

// Auth & identity
export type {
  User,
  AuthResponse,
  LoginPayload,
  ForgotPasswordPayload,
  ResetPasswordPayload,
  RegisterPayload,
  UserListFilters,
  CreateUserPayload,
  UpdateUserPayload,
  AssignUserRolesPayload,
} from "./user";
export type { Role, RoleSlug, Permission } from "./role";
export type { Tenant, TenantSetting } from "./tenant";
export type { MediaFile, MediaFileMetadata, MediaDimensions, MediaVisibility } from "./media";

// Course content
export type {
  Course,
  CourseStatus,
  CourseVisibility,
  CourseCategory,
  CourseTag,
  Module,
  Lesson,
  LessonContentType,
  LessonResource,
  ResourceType,
  CourseListFilters,
  CreateCoursePayload,
  UpdateCoursePayload,
  CreateModulePayload,
  UpdateModulePayload,
  CreateLessonPayload,
  UpdateLessonPayload,
} from "./course";

// Learning
export type {
  Enrollment,
  EnrollmentStatus,
  LessonProgressStatus,
  LessonProgress,
  LessonContent,
  LessonContentMedia,
  EnrollmentProgressSummary,
  LearnerCourseListItem,
  LearnerCourseDetail,
  LearnerLesson,
  LearnerModule,
  EnrollmentListFilters,
  CreateEnrollmentPayload,
  BatchEnrollmentResult,
} from "./enrollment";
export type { Quiz, QuizQuestion, QuestionType, QuizOption, QuizAttempt, QuizAnswer } from "./quiz";
export type { Certificate } from "./certificate";
