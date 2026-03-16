import { useState } from 'react';
import dayjs from 'dayjs';
import './SearchForm.css';

export default function SearchForm({ onSearch, loading }) {
  const [form, setForm] = useState({
    location:   '',
    start_time: '',
    end_time:   '',
    max_price:  '',
  });
  const [errors, setErrors] = useState({});

  const handleChange = (e) => {
    const { name, value } = e.target;
    setForm((prev) => ({ ...prev, [name]: value }));
    if (errors[name]) setErrors((prev) => ({ ...prev, [name]: null }));
  };

  const validate = () => {
    const newErrors = {};
    const now = dayjs();

    if (!form.start_time) {
      newErrors.start_time = 'Start date/time is required.';
    } else if (!dayjs(form.start_time).isValid()) {
      newErrors.start_time = 'Start date/time is not valid.';
    } else if (dayjs(form.start_time).isBefore(now)) {
      newErrors.start_time = 'Start time cannot be in the past.';
    }

    if (!form.end_time) {
      newErrors.end_time = 'End date/time is required.';
    } else if (!dayjs(form.end_time).isValid()) {
      newErrors.end_time = 'End date/time is not valid.';
    } else if (form.start_time && !dayjs(form.end_time).isAfter(dayjs(form.start_time))) {
      newErrors.end_time = 'End time must be after start time.';
    }

    if (form.max_price && (isNaN(form.max_price) || Number(form.max_price) <= 0)) {
      newErrors.max_price = 'Max price must be a positive number.';
    }

    return newErrors;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    const validationErrors = validate();
    if (Object.keys(validationErrors).length > 0) {
      setErrors(validationErrors);
      return;
    }
    onSearch(form);
  };

  const FieldError = ({ field }) =>
    errors[field]
      ? <span className="field-error">{errors[field]}</span>
      : null;

  return (
    <div className="search-card">
      <h2 className="search-card__heading">Search for a space</h2>

      <form className="search-form" onSubmit={handleSubmit} noValidate>

        <div className="form-group form-group--full">
          <label className="form-label">Location</label>
          <input
            className="form-input"
            name="location"
            placeholder="e.g. City Center, Airport..."
            value={form.location}
            onChange={handleChange}
          />
        </div>

        <div className="form-group">
          <label className="form-label form-label--required">Start date & time</label>
          <input
            className={`form-input ${errors.start_time ? 'form-input--error' : ''}`}
            name="start_time"
            type="datetime-local"
            value={form.start_time}
            onChange={handleChange}
          />
          <FieldError field="start_time" />
        </div>

        <div className="form-group">
          <label className="form-label form-label--required">End date & time</label>
          <input
            className={`form-input ${errors.end_time ? 'form-input--error' : ''}`}
            name="end_time"
            type="datetime-local"
            value={form.end_time}
            onChange={handleChange}
          />
          <FieldError field="end_time" />
        </div>

        <div className="form-group">
          <label className="form-label">Max price per hour (£)</label>
          <input
            className={`form-input ${errors.max_price ? 'form-input--error' : ''}`}
            name="max_price"
            type="number"
            min="0"
            step="0.01"
            placeholder="e.g. 6.00"
            value={form.max_price}
            onChange={handleChange}
          />
          <FieldError field="max_price" />
        </div>

        <div className="search-form__footer">
          <button className="btn btn--primary" type="submit" disabled={loading}>
            {loading ? 'Searching...' : 'Search spaces'}
          </button>
        </div>

      </form>
    </div>
  );
}