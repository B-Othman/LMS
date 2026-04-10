export type PackageStandard = 'scorm_12' | 'scorm_2004' | 'xapi' | 'native';
export type PackageStatus = 'uploaded' | 'validating' | 'valid' | 'invalid' | 'published' | 'failed';
export type LaunchSessionStatus = 'active' | 'completed' | 'failed' | 'abandoned';

export interface ScormScoItem {
  identifier: string;
  title: string;
  href: string;
  sco_type: string;
}

export interface ContentPackageVersion {
  id: number;
  version_number: number;
  launch_path: string;
  sco_count: number;
  metadata: {
    title?: string;
    description?: string;
    identifier?: string;
    version?: string;
  } | null;
  scos: ScormScoItem[];
  created_at: string;
}

export interface ContentPackage {
  id: number;
  course_id: number;
  title: string;
  standard: PackageStandard;
  original_filename: string;
  file_size_bytes: number;
  status: PackageStatus;
  error_message: string | null;
  version: ContentPackageVersion | null;
  created_at: string;
  updated_at: string;
}

export interface ScormLaunchResult {
  session_id: number;
  launch_url: string;
}
