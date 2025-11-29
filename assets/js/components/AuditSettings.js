import { __, sprintf } from "@wordpress/i18n";
import { Card, CardBody, CardHeader, TextControl, Button, ToggleControl, Notice } from "@wordpress/components";
import { useState } from "@wordpress/element";

function AuditSettings({ onSettingsSaved }) {
    const [retentionDays, setRetentionDays] = useState(90);
    const [failedRetentionDays, setFailedRetentionDays] = useState(180);
    const [autoCleanup, setAutoCleanup] = useState(true);
    const [saving, setSaving] = useState(false);
    const [cleaning, setCleaning] = useState(false);
    const [message, setMessage] = useState(null);

    const saveSettings = () => {
        setSaving(true);
        setMessage(null);

        fetch(saltShakerAuditData.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "salt_shaker_save_audit_settings",
                nonce: saltShakerAuditData.nonce,
                retention_days: retentionDays,
                failed_retention_days: failedRetentionDays,
                auto_cleanup_enabled: autoCleanup,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    setMessage({
                        type: "success",
                        text: data.data.message,
                    });
                    if (onSettingsSaved) {
                        onSettingsSaved();
                    }
                } else {
                    setMessage({
                        type: "error",
                        text: data.data || __("Failed to save settings", "salt-shaker"),
                    });
                }
            })
            .catch((err) => {
                setMessage({
                    type: "error",
                    text: __("Network error while saving settings", "salt-shaker"),
                });
                console.error("Error saving settings:", err);
            })
            .finally(() => {
                setSaving(false);
            });
    };

    const cleanupNow = () => {
        if (!confirm(__("Are you sure you want to clean up old logs now?", "salt-shaker"))) {
            return;
        }

        setCleaning(true);
        setMessage(null);

        fetch(saltShakerAuditData.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "salt_shaker_cleanup_audit_logs",
                nonce: saltShakerAuditData.nonce,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    setMessage({
                        type: "success",
                        text: data.data.message,
                    });
                    if (onSettingsSaved) {
                        onSettingsSaved();
                    }
                } else {
                    setMessage({
                        type: "error",
                        text: data.data || __("Failed to cleanup logs", "salt-shaker"),
                    });
                }
            })
            .catch((err) => {
                setMessage({
                    type: "error",
                    text: __("Network error while cleaning up logs", "salt-shaker"),
                });
                console.error("Error cleaning up logs:", err);
            })
            .finally(() => {
                setCleaning(false);
            });
    };

    return (
        <Card>
            <CardHeader>
                <h2>{__("Audit Log Settings", "salt-shaker")}</h2>
            </CardHeader>
            <CardBody>
                {message && (
                    <Notice
                        status={message.type}
                        isDismissible={true}
                        onRemove={() => setMessage(null)}
                    >
                        {message.text}
                    </Notice>
                )}

                <div className="salt-shaker-settings-row">
                    <TextControl
                        label={__("Data Retention (days)", "salt-shaker")}
                        help={__("Keep successful rotation logs for this many days (1-365)", "salt-shaker")}
                        type="number"
                        min="1"
                        max="365"
                        value={retentionDays}
                        onChange={(value) => setRetentionDays(parseInt(value) || 90)}
                    />
                </div>

                <div className="salt-shaker-settings-row">
                    <TextControl
                        label={__("Failed Rotation Retention (days)", "salt-shaker")}
                        help={__("Keep failed rotation logs for this many days (1-730)", "salt-shaker")}
                        type="number"
                        min="1"
                        max="730"
                        value={failedRetentionDays}
                        onChange={(value) => setFailedRetentionDays(parseInt(value) || 180)}
                    />
                </div>

                <div className="salt-shaker-settings-row">
                    <ToggleControl
                        label={__("Auto Cleanup Enabled", "salt-shaker")}
                        help={__("Automatically clean up old logs daily", "salt-shaker")}
                        checked={autoCleanup}
                        onChange={setAutoCleanup}
                    />
                </div>

                <div className="salt-shaker-settings-actions">
                    <Button
                        variant="primary"
                        onClick={saveSettings}
                        isBusy={saving}
                        disabled={saving}
                    >
                        {saving ? __("Saving...", "salt-shaker") : __("Save Settings", "salt-shaker")}
                    </Button>
                    <Button
                        variant="secondary"
                        onClick={cleanupNow}
                        isBusy={cleaning}
                        disabled={cleaning}
                    >
                        {cleaning ? __("Cleaning...", "salt-shaker") : __("Cleanup Old Logs Now", "salt-shaker")}
                    </Button>
                </div>
            </CardBody>
        </Card>
    );
}

export default AuditSettings;
