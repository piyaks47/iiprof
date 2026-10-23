/** Shapes returned by the Laravel API (/api/v1). Keep in sync with app/Http/Controllers/Api/V1. */

export interface SiteSettings {
  site_name: string;
  short_name: string | null;
  tagline: string | null;
  meta_description: string | null;
  register_url: string | null;
  contact_email: string | null;
  contact_phone: string | null;
  address: string | null;
  facebook_url: string | null;
  instagram_url: string | null;
  linkedin_url: string | null;
  x_url: string | null;
  youtube_url: string | null;
  whatsapp_url: string | null;
  footer_text: string | null;
  logo_url: string | null;
  /** Banner image for pages that have none of their own. */
  default_banner_url: string | null;
  /** Sign-in page (admin / member area). */
  login_logo_url?: string | null;
  login_background_url?: string | null;
}

/** Navigation item; up to three levels deep. `url` is null for drop-down-only items. */
export interface MenuItem {
  id: number;
  label: string;
  url: string | null;
  new_tab: boolean;
  /** Featured image of this item (items without one use their parent's — see menuImage()). */
  image_url?: string | null;
  children: MenuItem[];
}

export interface Announcement {
  id: number;
  text: string;
  url: string | null;
}

export interface FooterLink {
  id: number;
  label: string;
  url: string;
  new_tab: boolean;
}

export interface FooterColumn {
  id: number;
  title: string;
  links: FooterLink[];
}

export interface SocialLink {
  key: 'facebook' | 'instagram' | 'linkedin' | 'x' | 'youtube' | 'whatsapp';
  label: string;
  url: string;
}

export interface Site {
  settings: SiteSettings;
  menu: MenuItem[];
  announcements: Announcement[];
  footer: FooterColumn[];
  socials: SocialLink[];
}

export interface HeroSlide {
  id: number;
  eyebrow: string | null;
  title: string;
  subtitle: string | null;
  image_url: string | null;
  button_text: string | null;
  button_url: string | null;
}

export interface Stat {
  id: number;
  value: string;
  label: string;
}

export interface Highlight {
  id: number;
  title: string;
  description: string | null;
  icon: string;
}

export interface EventSummary {
  id: number;
  title: string;
  slug: string;
  path: string;
  summary: string | null;
  image_url: string | null;
  /** YYYY-MM-DD */
  starts_on: string | null;
  location: string | null;
  button_text: string | null;
  button_url: string | null;
  /** Photos / video of the event (shown on past events). */
  media_url?: string | null;
  /** Its date has passed. */
  is_past?: boolean;
  /** Next-event block only. */
  description_html?: string;
}

export interface EventDetail extends EventSummary {
  /** Markdown — for React Native. */
  description: string | null;
  /** Sanitised HTML — for the web. */
  description_html: string;
}

export interface GalleryItem {
  id: number;
  title: string | null;
  image_url: string;
}

/** Item of the newer homepage blocks — the same shape for every block; each block uses some of the fields. */
export interface HomeItem {
  id: number;
  title: string;
  /** Tag / "who it is for" / detail line. */
  subtitle: string | null;
  /** Number (impact) or code (membership, e.g. FMIIP). */
  value: string | null;
  /** Markdown — for React Native. */
  description: string | null;
  /** Sanitised HTML — for the web. */
  description_html: string;
  icon: string | null;
  image_url: string | null;
  button_text: string | null;
  button_url: string | null;
}

/** News item, blog post or article. */
export interface PostSummary {
  id: number;
  type: string;
  type_label: string;
  title: string;
  slug: string;
  /** /news/<slug> */
  path: string;
  excerpt: string | null;
  image_url: string | null;
  author: string | null;
  /** YYYY-MM-DD */
  published_on: string | null;
  reading_minutes: number;
  /** When set, the card opens this address instead of the post page. */
  external_url: string | null;
  is_featured: boolean;
}

export interface Post extends PostSummary {
  /** Markdown (React Native) and sanitised HTML (web). */
  body: string | null;
  body_html: string;
  related: PostSummary[];
}

export interface PostList {
  data: PostSummary[];
  types: { key: string; label: string }[];
  meta: { current_page: number; last_page: number; total: number };
}

export interface SeminarSummary {
  id: number;
  title: string;
  slug: string;
  /** /seminars/<slug> */
  path: string;
  summary: string | null;
  image_url: string | null;
  /** Destination of the banner button; null → the seminar page. */
  link: string | null;
  button_text: string | null;
  starts_on: string | null;
  location: string | null;
}

export interface Seminar extends SeminarSummary {
  description: string | null;
  description_html: string;
  others: SeminarSummary[];
}

/** Corporate or institutional member. */
export interface MemberOrganisation {
  id: number;
  name: string;
  category: string | null;
  city: string | null;
  logo_url: string | null;
  website: string | null;
  description: string | null;
  member_since: string | null;
  grade: string | null;
  is_featured: boolean;
}

/** A member in the homepage member directory. */
export interface FeaturedMember {
  id: number;
  category: string;
  name: string;
  designation: string | null;
  organisation: string | null;
  city: string | null;
  photo_url: string | null;
  url: string | null;
}

/** A row of the membership fee table; amounts as typed in the admin ("1500", "Free", "On request"). */
export interface MembershipFee {
  id: number;
  grade: string;
  code: string | null;
  annual_fee: string | null;
  lifetime_fee: string | null;
  note: string | null;
  button_url: string | null;
}

/** A scheduled run of a training programme. */
export interface TrainingBatch {
  id: number;
  batch_code: string | null;
  format: string;
  format_label: string;
  location: string | null;
  /** YYYY-MM-DD */
  starts_on: string;
  ends_on: string | null;
  /** Formatted range, e.g. "12–14 Dec 2026". */
  dates: string;
  schedule: string | null;
  seats: number | null;
  register_url: string | null;
  programme?: { id: number; title: string; slug: string; path: string };
}

export interface TrainingType {
  key: string;
  label: string;
}

export interface TrainingProgrammeSummary {
  id: number;
  title: string;
  slug: string;
  /** /training/<slug> */
  path: string;
  type: string;
  type_label: string;
  tagline: string | null;
  summary: string | null;
  duration: string | null;
  format: string | null;
  fee: string | null;
  image_url: string | null;
  button_text: string | null;
  button_url: string | null;
  is_featured: boolean;
  next_batch: TrainingBatch | null;
}

export interface TrainingProgramme extends TrainingProgrammeSummary {
  /** Markdown (React Native) and sanitised HTML (web). */
  description: string | null;
  description_html: string;
  audience: string | null;
  audience_html: string;
  outcomes: string | null;
  outcomes_html: string;
  modules: string[];
  certificate: string | null;
  batches: TrainingBatch[];
  related: TrainingProgrammeSummary[];
}

export interface TrainingIndex {
  types: TrainingType[];
  programmes: TrainingProgrammeSummary[];
  batches: TrainingBatch[];
}

interface SectionBase<K extends string, I, X = null> {
  key: K;
  eyebrow: string | null;
  title: string | null;
  title_accent: string | null;
  subtitle: string | null;
  body: string | null;
  body_html: string;
  button_text: string | null;
  button_url: string | null;
  image_url: string | null;
  items: I[];
  /** Data besides the items (member directory, fee table, scheduled batches). */
  extra: X;
}

export type HomeSection =
  | SectionBase<'hero', HeroSlide>
  | SectionBase<'stats', Stat>
  | SectionBase<'about', never>
  | SectionBase<'highlights', Highlight>
  | SectionBase<'events', EventSummary>
  | SectionBase<'gallery', GalleryItem>
  | SectionBase<'pillars', HomeItem>
  | SectionBase<'impact', HomeItem>
  | SectionBase<'benefits', HomeItem>
  | SectionBase<'membership', HomeItem>
  | SectionBase<'programmes', HomeItem>
  | SectionBase<'testimonials', Testimonial>
  | SectionBase<'partners', HomeItem>
  | SectionBase<'faq', HomeItem>
  | SectionBase<'join', never>
  | SectionBase<'membership_hub', HomeItem, { members: FeaturedMember[]; member_categories: { key: string; label: string }[] }>
  | SectionBase<'membership_objectives', HomeItem>
  | SectionBase<'membership_apply', HomeItem, { fees: MembershipFee[] }>
  | SectionBase<'training', TrainingProgrammeSummary, { batches: TrainingBatch[]; types: TrainingType[] }>
  | SectionBase<'training_formats', HomeItem>
  | SectionBase<'training_benefits', HomeItem>
  | SectionBase<'faculty', HomeItem>
  | SectionBase<'next_event', EventSummary>
  | SectionBase<'past_events', EventSummary>
  | SectionBase<'news', PostSummary>
  | SectionBase<'seminars', SeminarSummary>
  | SectionBase<'board', TeamMember, { groups: TeamGroup[] }>
  | SectionBase<'corporate_members', MemberOrganisation>
  | SectionBase<'institutions', MemberOrganisation>
  | SectionBase<'contact', never>;

export type HomeSectionOf<K extends HomeSection['key']> = Extract<HomeSection, { key: K }>;

export type HomeSectionKey = HomeSection['key'];

export interface Home {
  sections: HomeSection[];
}

/** Top-of-page banner, edited per page in the admin. */
export interface PageBanner {
  /** The page's own image, else its menu item's (or the nearest one above it); null → `settings.default_banner_url`. */
  image_url: string | null;
  title: string;
  /** Longer description under the excerpt. */
  text: string | null;
  button_text: string | null;
  button_url: string | null;
}

export interface PageSummary {
  id: number;
  title: string;
  slug: string;
  /** Address on the website, e.g. /about-us or /khazana/lenders. */
  path: string;
  /** Short lead shown in the banner. */
  excerpt: string | null;
  featured_image_url: string | null;
  banner: PageBanner;
  published_at: string | null;
  updated_at: string | null;
}

export interface Page extends PageSummary {
  /** Raw Markdown — use this in React Native. */
  content: string | null;
  /** Sanitised HTML rendered by Laravel — use this on the web. */
  content_html: string;
  /** false → banner-only page (the website shows a landing page under the banner). */
  has_content: boolean;
  meta: { title: string; description: string | null };
}

export interface TeamGroup {
  key: string;
  title: string;
  path: string;
}

export interface TeamMember {
  id: number;
  group: string;
  name: string;
  role: string | null;
  /** Markdown. */
  bio: string | null;
  /** Sanitised HTML of the biography. */
  bio_html?: string;
  photo_url: string | null;
}

export interface Team {
  groups: TeamGroup[];
  members: TeamMember[];
}

export interface Testimonial {
  id: number;
  name: string;
  role: string | null;
  quote: string | null;
  photo_url: string | null;
  video_url: string | null;
  /** Privacy-friendly YouTube embed URL, when the video is on YouTube. */
  video_embed_url: string | null;
  /** Address of a video file (.mp4 / .webm) to play with <video>. */
  video_file_url: string | null;
}

export interface EnquiryInput {
  name: string;
  email: string;
  phone?: string;
  subject?: string;
  message: string;
  /** Honeypot — leave empty. */
  website?: string;
}

export interface ApiEnvelope<T> {
  data: T;
}
