import { apiGet, apiPost } from './client';
import type { ApiEnvelope, EnquiryInput, EventDetail, EventSummary, GalleryItem, Home, Page, PageSummary, Site, Post, PostList, Seminar, Team, Testimonial, TrainingIndex, TrainingProgramme } from '../types';

export const api = {
  site: (signal?: AbortSignal) => apiGet<ApiEnvelope<Site>>('/site', { signal }).then((r) => r.data),
  home: (signal?: AbortSignal) => apiGet<ApiEnvelope<Home>>('/home', { signal }).then((r) => r.data),
  pages: (signal?: AbortSignal) => apiGet<ApiEnvelope<PageSummary[]>>('/pages', { signal }).then((r) => r.data),
  page: (slug: string, signal?: AbortSignal) =>
    apiGet<ApiEnvelope<Page>>(`/pages/${encodeURIComponent(slug)}`, { signal }).then((r) => r.data),
  /** Page for a website address (`/about-us`, `/khazana/lenders`). 404 when there is none. */
  pageByPath: (path: string, signal?: AbortSignal) =>
    apiGet<ApiEnvelope<Page>>(`/page?path=${encodeURIComponent(path)}`, { signal }).then((r) => r.data),
  events: (signal?: AbortSignal) => apiGet<ApiEnvelope<EventSummary[]>>('/events', { signal }).then((r) => r.data),
  event: (slug: string, signal?: AbortSignal) =>
    apiGet<ApiEnvelope<EventDetail>>(`/events/${encodeURIComponent(slug)}`, { signal }).then((r) => r.data),
  /** ?when=upcoming | past */
  eventsWhen: (when: 'upcoming' | 'past', signal?: AbortSignal) => apiGet<ApiEnvelope<EventSummary[]>>(`/events?when=${when}`, { signal }).then((r) => r.data),
  news: (type = '', page = 1, signal?: AbortSignal) =>
    apiGet<PostList>(`/news?page=${page}${type ? `&type=${encodeURIComponent(type)}` : ''}`, { signal }),
  post: (slug: string, signal?: AbortSignal) => apiGet<ApiEnvelope<Post>>(`/news/${encodeURIComponent(slug)}`, { signal }).then((r) => r.data),
  seminar: (slug: string, signal?: AbortSignal) => apiGet<ApiEnvelope<Seminar>>(`/seminars/${encodeURIComponent(slug)}`, { signal }).then((r) => r.data),
  training: (signal?: AbortSignal) => apiGet<ApiEnvelope<TrainingIndex>>('/training', { signal }).then((r) => r.data),
  trainingProgramme: (slug: string, signal?: AbortSignal) =>
    apiGet<ApiEnvelope<TrainingProgramme>>(`/training/${encodeURIComponent(slug)}`, { signal }).then((r) => r.data),
  team: (group?: string, signal?: AbortSignal) =>
    apiGet<ApiEnvelope<Team>>(`/team${group ? `?group=${encodeURIComponent(group)}` : ''}`, { signal }).then((r) => r.data),
  testimonials: (signal?: AbortSignal) => apiGet<ApiEnvelope<Testimonial[]>>('/testimonials', { signal }).then((r) => r.data),
  gallery: (signal?: AbortSignal) => apiGet<ApiEnvelope<GalleryItem[]>>('/gallery', { signal }).then((r) => r.data),
  sendEnquiry: (input: EnquiryInput) => apiPost<{ message: string }>('/enquiries', input),
};
