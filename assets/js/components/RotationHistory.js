import { __, sprintf } from "@wordpress/i18n";
import { Button, Modal, Spinner, SelectControl, ToggleControl, TextControl, Notice } from "@wordpress/components";
import { useState, useEffect } from "@wordpress/element";

function RotationHistory() {
    const [logs, setLogs] = useState([]);
    const [loading, setLoading] = useState(true);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [statusFilter, setStatusFilter] = useState("");
    const [detailLog, setDetailLog] = useState(null);
    const [showDetail, setShowDetail] = useState(false);
    const [showSettings, setShowSettings] = useState(false);
    
    // Settings state
    const [retentionDays, setRetentionDays] = useState(90);
    const [failedRetentionDays, setFailedRetentionDays] = useState(180);
    const [autoCleanup, setAutoCleanup] = useState(true);
    const [savingSettings, setSavingSettings] = useState(false);
    const [cleaningUp, setCleaningUp] = useState(false);
    const [settingsMessage, setSettingsMessage] = useState(null);

    const fetchLogs = () => {
        setLoading(true);

        fetch(saltShakerData.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "salt_shaker_get_audit_logs",
                nonce: saltShakerData.nonce,
                page: page,
                per_page: 5,
                status: statusFilter,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    setLogs(data.data.logs || []);
                    setTotalPages(data.data.total_pages || 1);
                }
            })
            .catch((err) => {
                console.error("Error fetching logs:", err);
            })
            .finally(() => {
                setLoading(false);
            });
    };

    useEffect(() => {
        fetchLogs();
    }, [page, statusFilter]);

    const fetchSettings = () => {
        fetch(saltShakerData.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "salt_shaker_get_audit_settings",
                nonce: saltShakerData.nonce,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    setRetentionDays(data.data.retention_days);
                    setFailedRetentionDays(data.data.failed_retention_days);
                    setAutoCleanup(data.data.auto_cleanup_enabled);
                }
            })
            .catch((err) => console.error("Error fetching settings:", err));
    };

    const saveSettings = () => {
        setSavingSettings(true);
        setSettingsMessage(null);

        fetch(saltShakerData.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "salt_shaker_save_audit_settings",
                nonce: saltShakerData.nonce,
                retention_days: retentionDays,
                failed_retention_days: failedRetentionDays,
                auto_cleanup_enabled: autoCleanup,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    setSettingsMessage({ type: "success", text: data.data.message });
                } else {
                    setSettingsMessage({ type: "error", text: data.data || __("Failed to save", "salt-shaker") });
                }
            })
            .catch(() => {
                setSettingsMessage({ type: "error", text: __("Network error", "salt-shaker") });
            })
            .finally(() => setSavingSettings(false));
    };

    const cleanupNow = () => {
        if (!confirm(__("Delete old logs based on retention settings?", "salt-shaker"))) {
            return;
        }
        setCleaningUp(true);
        setSettingsMessage(null);

        fetch(saltShakerData.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "salt_shaker_cleanup_audit_logs",
                nonce: saltShakerData.nonce,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    setSettingsMessage({ type: "success", text: data.data.message });
                    fetchLogs();
                } else {
                    setSettingsMessage({ type: "error", text: data.data || __("Cleanup failed", "salt-shaker") });
                }
            })
            .catch(() => {
                setSettingsMessage({ type: "error", text: __("Network error", "salt-shaker") });
            })
            .finally(() => setCleaningUp(false));
    };

    const openSettings = () => {
        fetchSettings();
        setShowSettings(true);
        setSettingsMessage(null);
    };

    const formatDate = (dateString) => {
        const date = new Date(dateString.replace(" ", "T") + "Z");
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

    const handleFilterChange = (value) => {
        setStatusFilter(value);
        setPage(1);
    };

    return (
        <div className="salt-shaker-rotation-history">
            <div className="rotation-history-title">
                <h3>{__("Rotation History", "salt-shaker")}</h3>
                <Button
                    variant="link"
                    onClick={openSettings}
                    className="settings-link"
                >
                    {__("Settings", "salt-shaker")}
                </Button>
            </div>
            
            <div className="rotation-history-header">
                <SelectControl
                    value={statusFilter}
                    options={[
                        { label: __("All", "salt-shaker"), value: "" },
                        { label: __("Success", "salt-shaker"), value: "success" },
                        { label: __("Failed", "salt-shaker"), value: "failed" },
                    ]}
                    onChange={handleFilterChange}
                    __nextHasNoMarginBottom
                />
            </div>

            {loading ? (
                <div className="rotation-history-loading">
                    <Spinner />
                </div>
            ) : logs.length === 0 ? (
                <p className="no-logs">{__("No rotation history yet.", "salt-shaker")}</p>
            ) : (
                <>
                    <table className="rotation-history-table">
                        <thead>
                            <tr>
                                <th>{__("Date", "salt-shaker")}</th>
                                <th>{__("Method", "salt-shaker")}</th>
                                <th>{__("Status", "salt-shaker")}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody>
                            {logs.map((log) => (
                                <tr key={log.id}>
                                    <td>{formatDate(log.rotation_time)}</td>
                                    <td>{getMethodLabel(log.trigger_method)}</td>
                                    <td>{getStatusBadge(log.status)}</td>
                                    <td>
                                        <Button
                                            variant="link"
                                            onClick={() => viewDetail(log)}
                                        >
                                            {__("Details", "salt-shaker")}
                                        </Button>
                                    </td>
                                </tr>
                            ))}
                        </tbody>
                    </table>

                    {totalPages > 1 && (
                        <div className="rotation-history-pagination">
                            <Button
                                variant="secondary"
                                disabled={page === 1}
                                onClick={() => setPage(page - 1)}
                                size="small"
                            >
                                {__("←", "salt-shaker")}
                            </Button>
                            <span>
                                {sprintf(__("%d / %d", "salt-shaker"), page, totalPages)}
                            </span>
                            <Button
                                variant="secondary"
                                disabled={page === totalPages}
                                onClick={() => setPage(page + 1)}
                                size="small"
                            >
                                {__("→", "salt-shaker")}
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
                            {detailLog.duration_ms} {__("ms", "salt-shaker")}
                        </p>
                    </div>

                    <div className="detail-section">
                        <h3>{__("Triggered By", "salt-shaker")}</h3>
                        <p>
                            <strong>{__("User:", "salt-shaker")}</strong>{" "}
                            {detailLog.trigger_username}
                        </p>
                        <p>
                            <strong>{__("Method:", "salt-shaker")}</strong>{" "}
                            {getMethodLabel(detailLog.trigger_method)}
                        </p>
                    </div>

                    <div className="detail-section">
                        <h3>{__("Details", "salt-shaker")}</h3>
                        <p>
                            <strong>{__("Salt Source:", "salt-shaker")}</strong>{" "}
                            {detailLog.salt_source === "wordpress_api"
                                ? __("WordPress.org API", "salt-shaker")
                                : __("Local Generation", "salt-shaker")}
                        </p>
                        <p>
                            <strong>{__("Active Users:", "salt-shaker")}</strong>{" "}
                            {detailLog.affected_users}
                        </p>
                    </div>

                    {detailLog.error_message && (
                        <div className="detail-section detail-error">
                            <h3>{__("Error", "salt-shaker")}</h3>
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

            {showSettings && (
                <Modal
                    title={__("Log Cleanup Settings", "salt-shaker")}
                    onRequestClose={() => setShowSettings(false)}
                    className="salt-shaker-settings-modal"
                >
                    {settingsMessage && (
                        <Notice
                            status={settingsMessage.type}
                            isDismissible={true}
                            onRemove={() => setSettingsMessage(null)}
                        >
                            {settingsMessage.text}
                        </Notice>
                    )}

                    <div className="settings-field">
                        <TextControl
                            label={__("Keep successful logs (days)", "salt-shaker")}
                            type="number"
                            min="1"
                            max="365"
                            value={retentionDays}
                            onChange={(value) => setRetentionDays(parseInt(value) || 90)}
                        />
                    </div>

                    <div className="settings-field">
                        <TextControl
                            label={__("Keep failed logs (days)", "salt-shaker")}
                            type="number"
                            min="1"
                            max="730"
                            value={failedRetentionDays}
                            onChange={(value) => setFailedRetentionDays(parseInt(value) || 180)}
                        />
                    </div>

                    <div className="settings-field">
                        <ToggleControl
                            label={__("Auto cleanup (daily)", "salt-shaker")}
                            checked={autoCleanup}
                            onChange={setAutoCleanup}
                        />
                    </div>

                    <div className="modal-actions">
                        <Button
                            variant="secondary"
                            onClick={cleanupNow}
                            isBusy={cleaningUp}
                            disabled={cleaningUp || savingSettings}
                        >
                            {__("Cleanup Now", "salt-shaker")}
                        </Button>
                        <Button
                            variant="primary"
                            onClick={saveSettings}
                            isBusy={savingSettings}
                            disabled={savingSettings || cleaningUp}
                        >
                            {__("Save Settings", "salt-shaker")}
                        </Button>
                    </div>
                </Modal>
            )}
        </div>
    );
}

export default RotationHistory;
