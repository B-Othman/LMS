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
export type {
  QuizStatus,
  QuizQuestionType,
  QuizAttemptStatus,
  QuestionOption,
  QuizLatestAttemptSummary,
  QuizSummary,
  QuizAttemptQuizSummary,
  QuizQuestion,
  Quiz,
  QuizAttemptAnswerPayload,
  QuizAttemptQuestionAnswer,
  QuizAttemptQuestion,
  QuizAttempt,
  QuizAttemptListItem,
  CreateQuizPayload,
  UpdateQuizPayload,
  QuizQuestionOptionInput,
  CreateQuizQuestionPayload,
  UpdateQuizQuestionPayload,
  SubmitQuizAttemptPayload,
} from "./quiz";
export type {
  CertificateTemplateLayout,
  CertificateTemplateStatus,
  CertificateStatus,
  PublicCertificateVerificationStatus,
  CertificateTemplateSummary,
  CertificateTemplate,
  Certificate,
  CertificateDownloadLink,
  CertificateListFilters,
  CreateCertificateTemplatePayload,
  UpdateCertificateTemplatePayload,
  RevokeCertificatePayload,
  PublicCertificateVerification,
} from "./certificate";
export type {
  SearchUserResult,
  SearchCourseResult,
  SearchResults,
} from "./search";
export type {
  AuditActor,
  AuditLog,
  AuditLogChanges,
} from "./audit";
export type {
  ReportType,
  ExportFormat,
  ExportStatus,
  OverviewStats,
  CompletionRow,
  LearnerProgressRow,
  AssessmentRow,
  QuestionBreakdownRow,
  CourseDetailReport,
  ReportExport,
  CreateExportPayload,
} from "./report";
export type {
  PackageStandard,
  PackageStatus,
  LaunchSessionStatus,
  ScormScoItem,
  ContentPackageVersion,
  ContentPackage,
  ScormLaunchResult,
} from "./scorm";
export type {
  NotificationChannel,
  NotificationStatus,
  NotificationType,
  AppNotification,
  NotificationPreference,
  NotificationTemplate,
  UpdateNotificationTemplatePayload,
  UpdateNotificationPreferencesPayload,
} from "./notification";
