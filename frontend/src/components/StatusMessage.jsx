import './StatusMessage.css';

export default function StatusMessage({ status }) {
  if (!status) return null;

  return (
    <div className={`status-message status-message--${status.type}`}>
      <span className="status-message__dot" />
      {status.message}
    </div>
  );
}