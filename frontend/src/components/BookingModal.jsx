import dayjs from 'dayjs';
import duration from 'dayjs/plugin/duration';
import './BookingModal.css';

// Extend dayjs with duration plugin for hour calculation
dayjs.extend(duration);

/**
 * BookingModal
 *
 * Props:
 *  - space:    { id, location, hourly_price }  — the space being booked
 *  - filters:  { start_time, end_time }         — the searched time window
 *  - onConfirm: () => void                      — fires the actual API call
 *  - onCancel:  () => void                      — closes the modal
 *  - loading:   boolean                         — disables confirm while API is in-flight
 */
export default function BookingModal({ space, filters, onConfirm, onCancel, loading }) {
  if (!space || !filters) return null;

  // ── Calculate duration and total price ──────────────────────
  const start    = dayjs(filters.start_time);
  const end      = dayjs(filters.end_time);
  const diffMins = end.diff(start, 'minute');
  const hours    = diffMins / 60;

  // Format duration as "2h 30m" or "3h"
  const durationLabel = (() => {
    const h = Math.floor(hours);
    const m = diffMins % 60;
    if (m === 0) return `${h}h`;
    return `${h}h ${m}m`;
  })();

  const totalPrice = (hours * space.hourly_price).toFixed(2);

  // ── Format display times ─────────────────────────────────────
  const formatTime = (dt) => dayjs(dt).format('ddd D MMM, HH:mm');

  return (
    <div className="modal-overlay" onClick={onCancel}>
      {/* Stop click propagating to overlay when clicking inside modal */}
      <div className="modal" onClick={(e) => e.stopPropagation()}>

        <div className="modal__header">
          <div>
            <h2 className="modal__title">Confirm your booking</h2>
            <p className="modal__subtitle">Review the details before confirming</p>
          </div>
          <button className="modal__close" onClick={onCancel} aria-label="Close">
            ✕
          </button>
        </div>

        <div className="modal__body">
          <div className="booking-summary">

            <div className="summary-row">
              <span className="summary-row__label">Space</span>
              <span className="summary-row__value">#{space.id}</span>
            </div>

            <div className="summary-row">
              <span className="summary-row__label">Location</span>
              <span className="summary-row__value">{space.location}</span>
            </div>

            <div className="summary-row">
              <span className="summary-row__label">From</span>
              <span className="summary-row__value summary-row__value--mono">
                {formatTime(filters.start_time)}
              </span>
            </div>

            <div className="summary-row">
              <span className="summary-row__label">To</span>
              <span className="summary-row__value summary-row__value--mono">
                {formatTime(filters.end_time)}
              </span>
            </div>

            <div className="summary-row">
              <span className="summary-row__label">Duration</span>
              <span className="summary-row__value">{durationLabel}</span>
            </div>

            <div className="summary-row">
              <span className="summary-row__label">Rate</span>
              <span className="summary-row__value summary-row__value--mono">
                £{space.hourly_price.toFixed(2)}/hr
              </span>
            </div>

            <div className="summary-divider" />

            <div className="summary-row summary-row--total">
              <span className="summary-row__label">Total</span>
              <span className="summary-row__value">£{totalPrice}</span>
            </div>

          </div>

        </div>

        <div className="modal__footer">
          <button
            className="btn btn--primary btn--confirm"
            onClick={onConfirm}
            disabled={loading}
          >
            {loading ? 'Confirming...' : `Confirm & pay £${totalPrice}`}
          </button>
          <button className="btn--cancel" onClick={onCancel}>
            Cancel
          </button>
        </div>

      </div>
    </div>
  );
}