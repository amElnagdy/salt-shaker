import {__} from "@wordpress/i18n";
import {useEffect, useState} from "@wordpress/element";
import {Icon, Notice, Spinner} from "@wordpress/components";

function CurrentSalts({isConfigWritable}) {
    const [loading, setLoading] = useState(true);
    const [salts, setSalts] = useState({});
    const [isOpen, setIsOpen] = useState(false);

    useEffect(() => {
        if (!isConfigWritable) {
            setLoading(false);
            return;
        }

        jQuery.ajax({
            url: saltShakerData.ajaxUrl,
            type: "POST",
            data: {
                action: "salt_shaker_get_settings",
                nonce: saltShakerData.nonce,
            },
            success: function (response) {
                if (response.success) {
                    setSalts(response.data.currentSalts);
                }
                setLoading(false);
            },
            error: function () {
                setLoading(false);
            },
        });
    }, [isConfigWritable]);

    if (loading) {
        return <Spinner/>;
    }

    return (
        <div className={`salt-shaker-current-salts ${isOpen ? "is-open" : ""}`}>
            <div className="header" onClick={() => setIsOpen(!isOpen)}>
                <h3>{__("Current Salt Keys:", "salt-shaker")}</h3>
                <div className="toggle-section">
          <span className="toggle-text">
            {isOpen
                ? __("Click to hide", "salt-shaker")
                : __("Click to view", "salt-shaker")}
          </span>
                    <Icon icon={isOpen ? "arrow-up-alt2" : "arrow-down-alt2"}/>
                </div>
            </div>
            <div className="content">
                {!isConfigWritable ? (
                    <Notice
                        status="error"
                        isDismissible={false}
                        className="salt-shaker-notice"
                    >
                        {__(
                            "Salt Shaker cannot access your wp-config.php file. Please check file permissions or contact your hosting provider.",
                            "salt-shaker"
                        )}
                    </Notice>
                ) : (
                    <>
                        <p className="description">
                            {__(
                                "The following table shows the current set of the salt keys in the configuration file.",
                                "salt-shaker"
                            )}
                        </p>
                        <table className="wp-list-table widefat fixed striped">
                            <thead>
                            <tr>
                                <th>{__("Name", "salt-shaker")}</th>
                                <th>{__("Value", "salt-shaker")}</th>
                            </tr>
                            </thead>
                            <tbody>
                            {Object.entries(salts).map(([key, value]) => (
                                <tr key={key}>
                                    <td>{key}</td>
                                    <td className="salt-value">
                                        {value || __("Not set", "salt-shaker")}
                                    </td>
                                </tr>
                            ))}
                            </tbody>
                        </table>
                    </>
                )}
            </div>
        </div>
    );
}

export default CurrentSalts;
