import './ParkingSpaceList.css';

// Skeleton rows shown while loading
function LoadingRows() {
  return Array.from({ length: 3 }).map((_, i) => (
    <tr key={i} className="loading-row">
      <td><div className="skeleton" style={{ width: 24 }} /></td>
      <td><div className="skeleton" style={{ width: 180 }} /></td>
      <td><div className="skeleton" style={{ width: 60 }} /></td>
      <td><div className="skeleton" style={{ width: 72 }} /></td>
    </tr>
  ));
}

export default function ParkingSpaceList({ spaces, onBook, loading }) {
  return (
    <div className="results-section">
      <div className="results-header">
        <h3 className="results-heading">Available spaces</h3>
        {!loading && (
          <span className="results-count">{spaces.length} found</span>
        )}
      </div>

      <div className="table-card">
        {!loading && spaces.length === 0 ? (
          <div className="empty-state">
            <span className="empty-state__icon">🅿️</span>
            <p className="empty-state__text">No spaces available</p>
            <p className="empty-state__sub">Try adjusting your filters or time range</p>
          </div>
        ) : (
          <table className="parking-table">
            <thead>
              <tr>
                <th>#</th>
                <th>Location</th>
                <th>Price</th>
                <th>Action</th>
              </tr>
            </thead>
            <tbody>
              {loading
                ? <LoadingRows />
                : spaces.map((space) => (
                    <tr key={space.id}>
                      <td className="cell-id">{space.id}</td>
                      <td>
                        <span className="cell-location">{space.location}</span>
                      </td>
                      <td>
                        <span className="cell-price">
                          £{space.hourly_price.toFixed(2)}
                          <span className="cell-price__unit">/hr</span>
                        </span>
                      </td>
                      <td>
                        <button
                          className="btn--book"
                          onClick={() => onBook(space.id)}
                        >
                          Book now
                        </button>
                      </td>
                    </tr>
                  ))
              }
            </tbody>
          </table>
        )}
      </div>
    </div>
  );
}