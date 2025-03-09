import {__} from "@wordpress/i18n";
import {useEffect, useState} from "@wordpress/element";
import {Button, CheckboxControl, Notice, SelectControl, Spinner,} from "@wordpress/components";

function ScheduledChange({isConfigWritable}) {
    const [loading, setLoading] = useState(true);
    const [saving, setSaving] = useState(false);
    const [message, setMessage] = useState(null);
    const [settings, setSettings] = useState({
        autoUpdateEnabled: false,
        updateInterval: "weekly",
        nextScheduledDate: null,
    });

    useEffect(() => {
        jQuery.ajax({
            url: saltShakerData.ajaxUrl,
            type: "POST",
            data: {
                action: "salt_shaker_get_settings",
                nonce: saltShakerData.nonce,
            },
            success: function (response) {
                if (response.success) {
                    setSettings({
                        autoUpdateEnabled: response.data.autoUpdateEnabled,
                        updateInterval: response.data.updateInterval,
                        nextScheduledDate: response.data.nextScheduledDate || null,
                    });
                }
                setLoading(false);
            },
            error: function () {
                setLoading(false);
            },
        });
    }, []);

    const handleSaveSettings = () => {
        setSaving(true);
        jQuery.ajax({
            url: saltShakerData.ajaxUrl,
            type: "POST",
            data: {
                action: "salt_shaker_save_settings",
                nonce: saltShakerData.nonce,
                autoUpdateEnabled: settings.autoUpdateEnabled,
                updateInterval: settings.updateInterval,
            },
            success: function (response) {
                if (response.success) {
                    setMessage({
                        type: "success",
                        content:
                            response.data.message ||
                            __("Settings saved successfully", "salt-shaker"),
                    });

                    // Refresh the page after a short delay
                    setTimeout(() => {
                        window.location.reload();
                    }, 1500);
                } else {
                    setMessage({
                        type: "error",
                        content: response.data,
                    });
                    setSaving(false);
                }
            },
            error: function (xhr, status, error) {
                setMessage({
                    type: "error",
                    content:
                        error ||
                        __("An error occurred while saving settings.", "salt-shaker"),
                });
                setSaving(false);
            },
        });
    };

    if (loading) {
        return <Spinner/>;
    }

    return (
        <div className="salt-shaker-scheduled-change">
            <h3>{__("Scheduled Salt Keys Change:", "salt-shaker")}</h3>

            {!isConfigWritable && (
                <Notice
                    status="error"
                    isDismissible={false}
                    className="salt-shaker-notice"
                >
                    {__(
                        "Salt Shaker cannot modify your wp-config.php file. Please check file permissions or contact your hosting provider.",
                        "salt-shaker"
                    )}
                </Notice>
            )}

            {message && (
                <Notice
                    status={message.type}
                    onRemove={() => setMessage(null)}
                    isDismissible={true}
                >
                    {message.content}
                </Notice>
            )}

            <div className="settings-container">
                <div className="setting-row">
                    <CheckboxControl
                        label={__("Enable automatic salt key updates", "salt-shaker")}
                        checked={settings.autoUpdateEnabled}
                        onChange={(checked) =>
                            setSettings({...settings, autoUpdateEnabled: checked})
                        }
                        disabled={!isConfigWritable}
                    />
                </div>

                {settings.autoUpdateEnabled && (
                    <>
                        <div className="setting-row">
                            <SelectControl
                                label={__("Update Interval", "salt-shaker")}
                                value={settings.updateInterval}
                                options={[
                                    {label: __("Daily", "salt-shaker"), value: "daily"},
                                    {label: __("Weekly", "salt-shaker"), value: "weekly"},
                                    {label: __("Monthly", "salt-shaker"), value: "monthly"},
                                    {label: __("Quarterly", "salt-shaker"), value: "quarterly"},
                                    {label: __("Biannually", "salt-shaker"), value: "biannually"},
                                ]}
                                onChange={(value) =>
                                    setSettings({...settings, updateInterval: value})
                                }
                                disabled={!isConfigWritable}
                            />
                        </div>

                        {settings.nextScheduledDate && (
                            <div className="next-scheduled-update">
                                <p>
                                    {__(
                                        "The salt keys will be automatically changed on",
                                        "salt-shaker"
                                    )}{" "}
                                    <strong>{settings.nextScheduledDate}</strong>
                                </p>
                            </div>
                        )}

                        <div className="setting-row premium-feature">
                            <div className="premium-feature-header">
                                <h4>{__("Specific Day & Time", "salt-shaker")}</h4>
                                <div className="premium-badge">
                  <span className="premium-text">
                    {__("Premium", "salt-shaker")}
                  </span>
                                </div>
                            </div>

                            <div className="premium-feature-content">
                                <div className="premium-feature-row">
                                    <SelectControl
                                        label={__("Day", "salt-shaker")}
                                        value=""
                                        options={[
                                            {label: __("Select a day", "salt-shaker"), value: ""},
                                        ]}
                                        disabled={true}
                                    />
                                </div>

                                <div className="premium-feature-row">
                                    <SelectControl
                                        label={__("Time", "salt-shaker")}
                                        value=""
                                        options={[
                                            {label: __("Select a time", "salt-shaker"), value: ""},
                                        ]}
                                        disabled={true}
                                    />
                                </div>

                                <h4>{__("Additional Features", "salt-shaker")}</h4>

                                <div className="premium-feature-item">
                                    <CheckboxControl
                                        label={__(
                                            "Remind me to update the keys manually",
                                            "salt-shaker"
                                        )}
                                        checked={false}
                                        onChange={() => {
                                        }}
                                        disabled={true}
                                    />
                                    <div className="premium-badge">
                    <span className="premium-text">
                      {__("Premium", "salt-shaker")}
                    </span>
                                    </div>
                                </div>

                                <div className="premium-feature-item">
                                    <CheckboxControl
                                        label={__(
                                            "Notify me when an automatic update takes place",
                                            "salt-shaker"
                                        )}
                                        checked={false}
                                        onChange={() => {
                                        }}
                                        disabled={true}
                                    />
                                    <div className="premium-badge">
                    <span className="premium-text">
                      {__("Premium", "salt-shaker")}
                    </span>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <div className="premium-message">
                            <p>
                                {__(
                                    "Upgrade to Premium for specific scheduling, reminders, and notifications.",
                                    "salt-shaker"
                                )}{" "}
                                <a
                                    href="#salt-shaker-pro-features"
                                >
                                    {__("Upgrade Now", "salt-shaker")}
                                </a>
                            </p>
                        </div>
                    </>
                )}

                <div className="setting-row">
                    <Button
                        isPrimary
                        onClick={handleSaveSettings}
                        disabled={saving || !isConfigWritable}
                    >
                        {saving ? <Spinner/> : __("Save Settings", "salt-shaker")}
                    </Button>
                </div>
            </div>
        </div>
    );
}

export default ScheduledChange;
