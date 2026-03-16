import { useState } from 'react';
import dayjs from 'dayjs';
import { getAvailableSpaces, createBooking } from '../api/parkingApi';

export function useParking() {
  const [spaces,       setSpaces]       = useState([]);
  const [loading,      setLoading]      = useState(false);
  const [booking,      setBooking]      = useState(false);  // in-flight booking request
  const [status,       setStatus]       = useState(null);
  const [filters,      setFilters]      = useState(null);
  const [pendingSpace, setPendingSpace] = useState(null);   // space awaiting confirmation

  const formatDateTime = (dt) =>
    dayjs(dt).format('YYYY-MM-DD HH:mm:ss');

  // ── Search ───────────────────────────────────────────────────
  const search = async (formValues) => {
    // Clear previous results and status immediately on every new search
    // attempt — this ensures stale results never persist after a failed search
    setSpaces([]);
    setStatus(null);
    setFilters(null);
    setLoading(true);

    const params = {
      start_time: formatDateTime(formValues.start_time),
      end_time:   formatDateTime(formValues.end_time),
      ...(formValues.location  && { location:  formValues.location }),
      ...(formValues.max_price && { max_price: formValues.max_price }),
    };

    try {
      const res = await getAvailableSpaces(params);
      setSpaces(res.data.data);
      setFilters(params);
      if (res.data.data.length === 0) {
        setStatus({ type: 'info', message: 'No spaces available for this time range.' });
      }
    } catch (err) {
      // Spaces already cleared above — just set the appropriate error message
      const is422 = err.response?.status === 422;
      setStatus({
        type: 'error',
        message: is422
          ? 'Please check your search inputs and try again.'
          : 'Failed to fetch spaces. Please try again.',
      });
    } finally {
      setLoading(false);
    }
  };

  // ── Open modal — called when user clicks "Book now" ──────────
  const openBookingModal = (spaceId) => {
    const space = spaces.find((s) => s.id === spaceId);
    setPendingSpace(space);
  };

  // ── Close modal — called on Cancel or overlay click ──────────
  const closeBookingModal = () => {
    setPendingSpace(null);
  };

  // ── Confirm booking — called on modal Confirm button ─────────
  const confirmBooking = async () => {
    if (!pendingSpace) return;

    setBooking(true);
    try {
      await createBooking({
        parking_space_id: pendingSpace.id,
        user_id:          1,
        start_time:       filters.start_time,
        end_time:         filters.end_time,
      });

      setPendingSpace(null);
      setStatus({
        type:    'success',
        message: `Space #${pendingSpace.id} at ${pendingSpace.location} booked successfully!`,
      });

      // Refresh results — booked space should now disappear from list
      const res = await getAvailableSpaces(filters);
      setSpaces(res.data.data);

    } catch (err) {
      setPendingSpace(null);
      const is409 = err.response?.status === 409;
      setStatus({
        type:    'error',
        message: is409
          ? 'This space was just taken by someone else. Please choose another.'
          : 'Booking failed. Please try again.',
      });
    } finally {
      setBooking(false);
    }
  };

  return {
    spaces,
    loading,
    booking,
    status,
    filters,
    pendingSpace,
    search,
    openBookingModal,
    closeBookingModal,
    confirmBooking,
  };
}