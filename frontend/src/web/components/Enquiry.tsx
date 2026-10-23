import { createContext, useCallback, useContext, useEffect, useId, useMemo, useRef, useState, type ReactNode } from 'react';
import { ApiError, fieldErrors } from '@shared/api/client';
import { useSendEnquiry, useSite } from '@shared/hooks/queries';
import type { EnquiryInput } from '@shared/types';
import { useScrollLock } from '../lib/motion';
import { Icon } from './Icon';
import { button, cx } from './ui';

interface EnquiryApi {
  open: (subject?: string) => void;
}

const EnquiryContext = createContext<EnquiryApi>({ open: () => {} });

export const useEnquiry = () => useContext(EnquiryContext);

/** Provides the floating "Enquire" tab and the slide-over form used everywhere on the site. */
export function EnquiryProvider({ children }: { children: ReactNode }) {
  const [isOpen, setOpen] = useState(false);
  const [subject, setSubject] = useState('');
  const trigger = useRef<HTMLElement | null>(null);

  const open = useCallback((preset?: string) => {
    trigger.current = document.activeElement as HTMLElement | null;
    setSubject(preset ?? '');
    setOpen(true);
  }, []);

  const close = useCallback(() => {
    setOpen(false);
    trigger.current?.focus?.();
  }, []);

  const api = useMemo(() => ({ open }), [open]);

  return (
    <EnquiryContext.Provider value={api}>
      {children}
      <button
        type="button"
        onClick={() => open()}
        className="fixed right-0 top-1/2 z-40 hidden -translate-y-1/2 translate-x-1 items-center gap-2 rounded-l-xl bg-gradient-to-b from-gold-400 to-gold-500 px-2.5 py-5 text-[11px] font-bold uppercase tracking-[0.25em] text-navy-950 shadow-[0_10px_40px_-10px_rgb(192_148_67/0.8)] transition duration-300 hover:translate-x-0 hover:pr-3.5 sm:flex"
        style={{ writingMode: 'vertical-rl' }}
        aria-haspopup="dialog"
      >
        <Icon name="chat" className="size-4 rotate-90" strokeWidth={2} />
        Enquire
      </button>
      <EnquiryDrawer open={isOpen} subject={subject} onClose={close} />
    </EnquiryContext.Provider>
  );
}

const empty: EnquiryInput = { name: '', email: '', phone: '', subject: '', message: '', website: '' };

function EnquiryDrawer({ open, subject, onClose }: { open: boolean; subject: string; onClose: () => void }) {
  const titleId = useId();
  const { data: site } = useSite();
  const send = useSendEnquiry();
  const [form, setForm] = useState<EnquiryInput>(empty);
  const [done, setDone] = useState(false);
  const firstField = useRef<HTMLInputElement>(null);
  const panel = useRef<HTMLDivElement>(null);

  useScrollLock(open);

  useEffect(() => {
    if (!open) return;
    setForm({ ...empty, subject });
    setDone(false);
    send.reset();
    const t = setTimeout(() => firstField.current?.focus(), 350);
    const onKey = (e: KeyboardEvent) => {
      if (e.key === 'Escape') onClose();
      if (e.key === 'Tab' && panel.current) {
        const focusable = panel.current.querySelectorAll<HTMLElement>('button, input, textarea, a[href]');
        const first = focusable[0];
        const last = focusable[focusable.length - 1];
        if (e.shiftKey && document.activeElement === first) {
          e.preventDefault();
          last?.focus();
        } else if (!e.shiftKey && document.activeElement === last) {
          e.preventDefault();
          first?.focus();
        }
      }
    };
    window.addEventListener('keydown', onKey);
    return () => {
      clearTimeout(t);
      window.removeEventListener('keydown', onKey);
    };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [open, subject]);

  const errors = fieldErrors(send.error);
  const generalError =
    send.isError && Object.keys(errors).length === 0
      ? send.error instanceof ApiError && send.error.status === 429
        ? 'You have sent several messages in a short time. Please wait a minute and try again.'
        : 'We could not send your message. Please check your connection and try again.'
      : null;
  const set = (field: keyof EnquiryInput) => (e: React.ChangeEvent<HTMLInputElement | HTMLTextAreaElement>) =>
    setForm((f) => ({ ...f, [field]: e.target.value }));

  const submit = (e: React.FormEvent) => {
    e.preventDefault();
    send.mutate(form, { onSuccess: () => setDone(true) });
  };

  return (
    <div className={cx('fixed inset-0 z-[60]', open ? 'pointer-events-auto' : 'pointer-events-none')} aria-hidden={!open}>
      <div
        className={cx('absolute inset-0 bg-navy-950/60 backdrop-blur-sm transition-opacity duration-500', open ? 'opacity-100' : 'opacity-0')}
        onClick={onClose}
      />
      <div
        ref={panel}
        role="dialog"
        aria-modal="true"
        aria-labelledby={titleId}
        inert={!open}
        className={cx(
          'absolute inset-y-0 right-0 flex w-full max-w-md flex-col bg-ivory-50 shadow-2xl transition-transform duration-700 ease-out-expo',
          open ? 'translate-x-0' : 'translate-x-full',
        )}
      >
        <div className="relative overflow-hidden bg-navy-950 px-7 pb-8 pt-7 text-white">
          <div className="bg-lattice absolute inset-0 opacity-60 [mask-image:linear-gradient(to_bottom,black,transparent)]" />
          <div className="absolute -right-16 -top-16 size-48 rounded-full bg-gold-400/20 blur-3xl" />
          <div className="relative flex items-start justify-between gap-4">
            <div>
              <p className="eyebrow text-gold-300">{subject || 'Enquiry'}</p>
              <h2 id={titleId} className="mt-3 text-3xl font-semibold">How can we help?</h2>
              <p className="mt-2 text-sm text-navy-200">Leave your details and our team will get back to you shortly.</p>
            </div>
            <button type="button" onClick={onClose} className="rounded-full p-2 text-navy-200 ring-1 ring-white/15 transition hover:rotate-90 hover:text-white" aria-label="Close">
              <Icon name="close" className="size-5" />
            </button>
          </div>
        </div>

        <div className="flex-1 overflow-y-auto px-7 py-8">
          {done ? (
            <div className="flex h-full flex-col items-center justify-center text-center">
              <svg viewBox="0 0 52 52" className="size-20 animate-pop text-gold-500" aria-hidden="true">
                <circle cx="26" cy="26" r="24" fill="none" stroke="currentColor" strokeWidth="1.5" opacity="0.35" />
                <path d="M15 27l7 7 15-16" fill="none" stroke="currentColor" strokeWidth="2.5" strokeLinecap="round" strokeLinejoin="round" strokeDasharray="48" className="animate-draw" />
              </svg>
              <h3 className="mt-6 text-2xl font-semibold text-navy-950">Thank you, {form.name.split(' ')[0]}</h3>
              <p className="mt-2 max-w-xs text-sm text-navy-600">Your message has reached us. We will be in touch at {form.email}.</p>
              <button type="button" onClick={onClose} className={cx(button.navy, 'mt-8')}>Close</button>
            </div>
          ) : (
            <form onSubmit={submit} noValidate className="space-y-6">
              <Field label="Full name" error={errors.name}>
                <input ref={firstField} value={form.name} onChange={set('name')} required autoComplete="name" className={inputClass(errors.name)} />
              </Field>
              <div className="grid gap-6 sm:grid-cols-2">
                <Field label="Email" error={errors.email}>
                  <input type="email" value={form.email} onChange={set('email')} required autoComplete="email" className={inputClass(errors.email)} />
                </Field>
                <Field label="Phone (optional)" error={errors.phone}>
                  <input type="tel" value={form.phone} onChange={set('phone')} autoComplete="tel" className={inputClass(errors.phone)} />
                </Field>
              </div>
              <Field label="Subject (optional)" error={errors.subject}>
                <input value={form.subject} onChange={set('subject')} className={inputClass(errors.subject)} />
              </Field>
              <Field label="Message" error={errors.message}>
                <textarea value={form.message} onChange={set('message')} required rows={4} className={cx(inputClass(errors.message), 'resize-none')} />
              </Field>
              {/* Honeypot for bots — hidden from people and screen readers. */}
              <input type="text" name="website" value={form.website} onChange={set('website')} tabIndex={-1} autoComplete="off" className="absolute -left-[9999px] size-px opacity-0" aria-hidden="true" />

              {generalError && <p className="rounded-lg bg-rose-50 px-4 py-3 text-sm text-rose-700">{generalError}</p>}

              <button type="submit" disabled={send.isPending} className={cx(button.navy, 'w-full')}>
                {send.isPending ? (
                  <>
                    <span className="size-4 animate-spin rounded-full border-2 border-white/30 border-t-white" /> Sending…
                  </>
                ) : (
                  <>
                    Send enquiry <Icon name="paper-airplane" className="size-4 text-gold-300 transition group-hover/btn:translate-x-1" strokeWidth={2} />
                  </>
                )}
              </button>

              {(site?.settings.contact_email || site?.settings.contact_phone) && (
                <p className="text-center text-xs text-navy-500">
                  Or reach us directly
                  {site?.settings.contact_email && (
                    <> at <a className="font-semibold text-navy-800 link-underline" href={`mailto:${site.settings.contact_email}`}>{site.settings.contact_email}</a></>
                  )}
                  {site?.settings.contact_phone && <> · {site.settings.contact_phone}</>}
                </p>
              )}
            </form>
          )}
        </div>
      </div>
    </div>
  );
}

function inputClass(error?: string) {
  return cx(
    'peer block w-full border-0 border-b bg-transparent px-0 py-2.5 text-[15px] text-navy-950 transition placeholder:text-navy-300 focus:ring-0 focus:outline-none',
    error ? 'border-rose-400 focus:border-rose-500' : 'border-navy-200 focus:border-gold-500',
  );
}

function Field({ label, error, children }: { label: string; error?: string; children: ReactNode }) {
  return (
    <label className="block">
      <span className="text-[11px] font-semibold uppercase tracking-[0.18em] text-navy-500">{label}</span>
      {children}
      {error && <span className="mt-1.5 block text-xs font-medium text-rose-600">{error}</span>}
    </label>
  );
}
