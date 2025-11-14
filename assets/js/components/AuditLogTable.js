import { __, sprintf } from "@wordpress/i18n";
import { Button, Modal } from "@wordpress/components";
import { useState } from "@wordpress/element";

function LogTable({ logs, page, totalPages, onPageChange }) {
    const [detailLog, setDetailLog] = useState(null);
    const [showDetail, setShowDetail] = useState(false);

    const formatDate = (dateString) => {
        const date = new Date(dateString.replace(" ", "T"));
        return date.toLocaleString();
    };

    const getStatusBadge = (status) => {
        const className = `status-badge status-${status}`;
        const icon = status === "success" ? "✓" : "✗";
        const label = status === "success" ? __("Success", "salt-shaker") : __("Failed", "salt-shaker");
        return (
            <span className={className}>
                {icon} {label}
            </span>
        );
    };

    const getMethodLabel = (method) => {
        const labels = {
            manual: __("Manual", "salt-shaker"),
            scheduled: __("Scheduled", "salt-shaker"),
            cli: __("CLI", "salt-shaker"),
            api: __("API", "salt-shaker"),
        };
        return labels[method] || method;
    };

    const viewDetail = (log) => {
        setDetailLog(log);
        setShowDetail(true);
    };

    const closeDetail = () => {
        setShowDetail(false);
        setDetailLog(null);
    };

    const copyToClipboard = (text) => {
        navigator.clipboard.writeText(text).then(() => {
            // Could show a toast notification here
        });
    };

    return (
        <div className="salt-shaker-log-table-container">
            {logs.length === 0 ? (
                <div className="salt-shaker-no-logs">
                    <p>{__("No audit logs found matching your filters.", "salt-shaker")}</p>
                </div>
            ) : (
                <>
                    <table className="salt-shaker-log-table">
                        <thead>
                            <tr>
                                <th>{__("Date & Time", "salt-shaker")}</th>
                                <th>{__("Triggered By", "salt-shaker")}</th>
                                <th>{__("Method", "salt-shaker")}</th>
                                <th>{__("Status", "salt-shaker")}</th>
                                <th>{__("Duration", "salt-shaker")}</th>
                                <th>{__("Actions", "salt-shaker")}</th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.map((log) => (
                                <tr key={log.id}>
                                    <td>{formatDate(log.rotation_time)}</td>
                                    <td>{log.trigger_username}</td>
                                    <td>{getMethodLabel(log.trigger_method)}</td>
                                    <td>{getStatusBadge(log.status)}</td>
                                    <td>{log.duration_ms}ms</td>
                                    <td>
                                        <Button
                                            variant="link"
                                            onClick={() => viewDetail(log)}
                                        >
                                            {__("View", "salt-shaker")}
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {totalPages > 1 && (
                        <div className="salt-shaker-pagination">
                            <Button
                                variant="secondary"
                                disabled={page === 1}
                                onClick={() => onPageChange(page - 1)}
                            >
                                {__("Previous", "salt-shaker")}
                            </Button>
                            <span className="pagination-info">
                                {sprintf(
                                    __("Page %d of %d", "salt-shaker"),
                                    page,
                                    totalPages
                                )}
                            </span>
                            <Button
                                variant="secondary"
                                disabled={page === totalPages}
                                onClick={() => onPageChange(page + 1)}
                            >
                                {__("Next", "salt-shaker")}
                            </Button>
                        </div>
                    )}
                </>
            )}

            {showDetail && detailLog && (
                <Modal
                    title={__("Rotation Details", "salt-shaker")}
                    onRequestClose={closeDetail}
                    className="salt-shaker-detail-modal"
                >
                    <div className="detail-section">
                        <h3>{__("Status", "salt-shaker")}</h3>
                        {getStatusBadge(detailLog.status)}
                    </div>

                    <div className="detail-section">
                        <h3>{__("Timing", "salt-shaker")}</h3>
                        <p>
                            <strong>{__("Rotation Time:", "salt-shaker")}</strong>{" "}
                            {formatDate(detailLog.rotation_time)}
                        </p>
                        <p>
                            <strong>{__("Duration:", "salt-shaker")}</strong>{" "}
                            {detailLog.duration_ms} {__("milliseconds", "salt-shaker")}
                        </p>
                    </div>

                    <div className="detail-section">
                        <h3>{__("Triggered By", "salt-shaker")}</h3>
                        <p>
                            <strong>{__("User:", "salt-shaker")}</strong>{" "}
                            {detailLog.trigger_username}
                            {detailLog.triggered_by > 0 && ` (ID: ${detailLog.triggered_by})`}
                        </p>
                        <p>
                            <strong>{__("Method:", "salt-shaker")}</strong>{" "}
                            {getMethodLabel(detailLog.trigger_method)}
                        </p>
                        {detailLog.ip_address && (
                            <p>
                                <strong>{__("IP Address:", "salt-shaker")}</strong>{" "}
                                {detailLog.ip_address}
                            </p>
                        )}
                        {detailLog.user_agent && (
                            <p>
                                <strong>{__("User Agent:", "salt-shaker")}</strong>{" "}
                                {detailLog.user_agent}
                            </p>
                        )}
                    </div>

                    <div className="detail-section">
                        <h3>{__("Impact", "salt-shaker")}</h3>
                        <p>
                            <strong>{__("Active Sessions Terminated:", "salt-shaker")}</strong>{" "}
                            {detailLog.affected_users} {__("users", "salt-shaker")}
                        </p>
                        <p>
                            <strong>{__("Salt Source:", "salt-shaker")}</strong>{" "}
                            {detailLog.salt_source === "wordpress_api"
                                ? __("WordPress.org API", "salt-shaker")
                                : __("Local Generation", "salt-shaker")}
                        </p>
                        {detailLog.config_file_path && (
                            <p>
                                <strong>{__("Config File:", "salt-shaker")}</strong>{" "}
                                <code>{detailLog.config_file_path}</code>
                            </p>
                        )}
                    </div>

                    {(detailLog.old_salt_hash || detailLog.new_salt_hash) && (
                        <div className="detail-section">
                            <h3>{__("Salt Verification Hashes", "salt-shaker")}</h3>
                            {detailLog.old_salt_hash && (
                                <p>
                                    <strong>{__("Previous:", "salt-shaker")}</strong>{" "}
                                    <code>{detailLog.old_salt_hash.substring(0, 20)}...</code>
                                    <Button
                                        variant="link"
                                        onClick={() => copyToClipboard(detailLog.old_salt_hash)}
                                    >
                                        {__("Copy", "salt-shaker")}
                                    </Button>
                                </p>
                            )}
                            {detailLog.new_salt_hash && (
                                <p>
                                    <strong>{__("Current:", "salt-shaker")}</strong>{" "}
                                    <code>{detailLog.new_salt_hash.substring(0, 20)}...</code>
                                    <Button
                                        variant="link"
                                        onClick={() => copyToClipboard(detailLog.new_salt_hash)}
                                    >
                                        {__("Copy", "salt-shaker")}
                                    </Button>
                                </p>
                            )}
                        </div>
                    )}

                    <div className="detail-section">
                        <h3>{__("System Information", "salt-shaker")}</h3>
                        {detailLog.wp_version && (
                            <p>
                                <strong>{__("WordPress Version:", "salt-shaker")}</strong>{" "}
                                {detailLog.wp_version}
                            </p>
                        )}
                        {detailLog.plugin_version && (
                            <p>
                                <strong>{__("Plugin Version:", "salt-shaker")}</strong>{" "}
                                {detailLog.plugin_version}
                            </p>
                        )}
                        {detailLog.schedule_interval && (
                            <p>
                                <strong>{__("Schedule:", "salt-shaker")}</strong>{" "}
                                {detailLog.schedule_interval}
                            </p>
                        )}
                    </div>

                    {detailLog.error_message && (
                        <div className="detail-section detail-error">
                            <h3>{__("Error Message", "salt-shaker")}</h3>
                            <p className="error-message">{detailLog.error_message}</p>
                        </div>
                    )}

                    <div className="modal-actions">
                        <Button variant="primary" onClick={closeDetail}>
                            {__("Close", "salt-shaker")}
                        </Button>
                    </div>
                </Modal>
            )}
        </div>
    );
}

export default LogTable;
