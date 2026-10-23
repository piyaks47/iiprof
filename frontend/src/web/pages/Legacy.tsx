import { useEffect } from 'react';
import { Navigate, useNavigate, useParams } from 'react-router';
import { useEnquiry } from '../components/Enquiry';

/** iiprof.com event links: /event/13/Scholarship-Test → /events/scholarship-test */
export function LegacyEvent() {
  const { slug = '' } = useParams();
  return <Navigate to={`/events/${slug.toLowerCase().replace(/[^a-z0-9]+/g, '-').replace(/^-|-$/g, '')}`} replace />;
}

/** /member/login → the sign-in page of the admin and member area (served by Laravel). */
export function LegacyLogin() {
  useEffect(() => {
    window.location.replace('/admin/login');
  }, []);
  return null;
}

/** /member/registration → homepage with the membership form open. */
export function LegacyRegister() {
  const enquiry = useEnquiry();
  const navigate = useNavigate();
  useEffect(() => {
    navigate('/', { replace: true });
    enquiry.open('Membership registration');
  }, [enquiry, navigate]);
  return null;
}
