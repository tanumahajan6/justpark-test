import './index.css';
import SearchForm from './components/SearchForm';
import ParkingSpaceList from './components/ParkingSpaceList';
import StatusMessage from './components/StatusMessage';
import BookingModal from './components/BookingModal';
import { useParking } from './hooks/useParking';

export default function App() {
  const {
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
  } = useParking();

  return (
    <div className="app-wrapper">
      <header className="app-header">
        <div className="app-header__logo">P</div>
        <span className="app-header__title">JustPark</span>
      </header>

      <main className="app-main">
        <SearchForm onSearch={search} loading={loading} />

        {status && <StatusMessage status={status} />}

        {(spaces.length > 0 || loading) && (
          <ParkingSpaceList
            spaces={spaces}
            onBook={openBookingModal}
            loading={loading}
          />
        )}
      </main>

      {/* Modal mounts outside main flow, only when a space is pending */}
      {pendingSpace && (
        <BookingModal
          space={pendingSpace}
          filters={filters}
          onConfirm={confirmBooking}
          onCancel={closeBookingModal}
          loading={booking}
        />
      )}
    </div>
  );
}