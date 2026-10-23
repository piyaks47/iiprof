import { keepPreviousData, useInfiniteQuery, useMutation, useQuery } from '@tanstack/react-query';
import { api } from '../api/endpoints';
import { ApiError } from '../api/client';
import type { EnquiryInput } from '../types';

export const HOME_SLUG = 'home';

export const queryKeys = {
  site: ['site'] as const,
  home: ['home'] as const,
  pages: ['pages'] as const,
  page: (slug: string) => ['page', slug] as const,
  pageByPath: (path: string) => ['page-path', path] as const,
  events: ['events'] as const,
  event: (slug: string) => ['event', slug] as const,
  news: (type: string) => ['news', type] as const,
  post: (slug: string) => ['post', slug] as const,
  seminar: (slug: string) => ['seminar', slug] as const,
  training: ['training'] as const,
  trainingProgramme: (slug: string) => ['training', slug] as const,
  team: (group: string) => ['team', group] as const,
  testimonials: ['testimonials'] as const,
  gallery: ['gallery'] as const,
};

/** Do not retry "not found". */
const retry = (count: number, error: Error) => !(error instanceof ApiError && error.isNotFound) && count < 2;

export function useSite() {
  return useQuery({ queryKey: queryKeys.site, queryFn: ({ signal }) => api.site(signal), staleTime: 5 * 60_000 });
}

export function useHome() {
  return useQuery({ queryKey: queryKeys.home, queryFn: ({ signal }) => api.home(signal), staleTime: 60_000 });
}

export function usePages() {
  return useQuery({ queryKey: queryKeys.pages, queryFn: ({ signal }) => api.pages(signal), staleTime: 60_000 });
}

export function usePage(slug: string, enabled = true) {
  return useQuery({ queryKey: queryKeys.page(slug), queryFn: ({ signal }) => api.page(slug, signal), staleTime: 60_000, retry, enabled });
}

/** Page (and its banner) for a website address. */
export function usePageByPath(path: string, enabled = true) {
  return useQuery({ queryKey: queryKeys.pageByPath(path), queryFn: ({ signal }) => api.pageByPath(path, signal), staleTime: 60_000, retry, enabled });
}

export function useEvents() {
  return useQuery({ queryKey: queryKeys.events, queryFn: ({ signal }) => api.events(signal), staleTime: 60_000 });
}

export function useEvent(slug: string) {
  return useQuery({ queryKey: queryKeys.event(slug), queryFn: ({ signal }) => api.event(slug, signal), staleTime: 60_000, retry });
}

/** News & blogs, 12 at a time ("Load more"). */
export function useNews(type = '') {
  return useInfiniteQuery({
    queryKey: queryKeys.news(type),
    queryFn: ({ pageParam, signal }) => api.news(type, pageParam, signal),
    initialPageParam: 1,
    getNextPageParam: (last) => (last.meta.current_page < last.meta.last_page ? last.meta.current_page + 1 : undefined),
    placeholderData: keepPreviousData,
    staleTime: 60_000,
  });
}

export function usePost(slug: string) {
  return useQuery({ queryKey: queryKeys.post(slug), queryFn: ({ signal }) => api.post(slug, signal), staleTime: 60_000, retry });
}

export function useSeminar(slug: string) {
  return useQuery({ queryKey: queryKeys.seminar(slug), queryFn: ({ signal }) => api.seminar(slug, signal), staleTime: 60_000, retry });
}

export function useTraining() {
  return useQuery({ queryKey: queryKeys.training, queryFn: ({ signal }) => api.training(signal), staleTime: 60_000 });
}

export function useTrainingProgramme(slug: string) {
  return useQuery({ queryKey: queryKeys.trainingProgramme(slug), queryFn: ({ signal }) => api.trainingProgramme(slug, signal), staleTime: 60_000, retry });
}

export function useTeam(group = '') {
  return useQuery({ queryKey: queryKeys.team(group), queryFn: ({ signal }) => api.team(group || undefined, signal), staleTime: 5 * 60_000 });
}

export function useTestimonials() {
  return useQuery({ queryKey: queryKeys.testimonials, queryFn: ({ signal }) => api.testimonials(signal), staleTime: 5 * 60_000 });
}

export function useGallery() {
  return useQuery({ queryKey: queryKeys.gallery, queryFn: ({ signal }) => api.gallery(signal), staleTime: 5 * 60_000 });
}

export function useSendEnquiry() {
  return useMutation({ mutationFn: (input: EnquiryInput) => api.sendEnquiry(input) });
}
