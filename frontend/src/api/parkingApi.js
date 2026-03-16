import axios from 'axios';

// Single axios instance with base URL — easy to swap for production
const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1', // hardcoded due to time constraint, otherwise this would go in .env
  headers: { 'Content-Type': 'application/json' },
});

/**
 * Fetch available parking spaces.
 * @param {Object} params - { start_time, end_time, location?, max_price? }
 */
export const getAvailableSpaces = (params) =>
  api.get('/parking-spaces', { params });

/**
 * Create a booking for a space.
 * @param {Object} data - { parking_space_id, user_id, start_time, end_time }
 */
export const createBooking = (data) =>
  api.post('/bookings', data);