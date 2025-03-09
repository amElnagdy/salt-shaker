import {__} from "@wordpress/i18n";
import {useState} from "@wordpress/element";
import {Button, Card, CardBody, CardHeader, Flex, FlexItem, Modal, Notice, Spinner,} from "@wordpress/components";

function ImmediateChange({isConfigWritable}) {
    const [loading, setLoading] = useState(false);
    const [message, setMessage] = useState(null);
    const [showConfirmModal, setShowConfirmModal] = useState(false);

    const handleChangeSalts = () => {
        setLoading(true);
        jQuery.ajax({
            url: saltShakerData.ajaxUrl,
            type: "POST",
            data: {
                action: "salt_shaker_change_salts",
                nonce: saltShakerData.nonce,
            },
            success: function (response) {
                setMessage({
                    type: response.success ? "success" : "error",
                    content: response.success
                        ? __(
                            "Salt keys have been updated. You will be redirected to login...",
                            "salt-shaker"
                        )
                        : response.data,
                });

                if (response.success) {
                    setTimeout(() => {
                        window.location.href =
                            saltShakerData.loginUrl +
                            (saltShakerData.loginUrl.includes("?") ? "&" : "?") +
                            "redirect_to=" +
                            encodeURIComponent(saltShakerData.adminUrl);
                    }, 3000);
                }

                setLoading(false);
            },
            error: function (xhr, status, error) {
                setMessage({
                    type: "error",
                    content:
                        error ||
                        __("An error occurred while changing salt keys.", "salt-shaker"),
                });
                setLoading(false);
            },
        });
    };

    const openConfirmModal = () => {
        setShowConfirmModal(true);
    };

    const closeConfirmModal = () => {
        setShowConfirmModal(false);
    };

    return (
        <div className="salt-shaker-immediate-change">
            <Card>
                <CardHeader>
                    <h3>{__("Manual Salt Keys Change", "salt-shaker")}</h3>
                </CardHeader>
                <CardBody>
                    <div className="immediate-change-container">
                        <div className="immediate-change-description">
                            <p>
                                {__(
                                    "You can manually change your WordPress salt keys at any time. This will log out all users (including you) and require everyone to log in again.",
                                    "salt-shaker"
                                )}
                            </p>
                            <p>
                                {__(
                                    "This is useful if you suspect unauthorized access to your site or want to force all users to re-authenticate.",
                                    "salt-shaker"
                                )}
                            </p>
                        </div>

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
                                isDismissible={false}
                                className="salt-shaker-notice"
                            >
                                {message.content}
                            </Notice>
                        )}

                        <Flex className="immediate-change-action" justify="flex-start">
                            <FlexItem>
                                <Button
                                    variant="primary"
                                    onClick={openConfirmModal}
                                    disabled={loading || !isConfigWritable}
                                    className="change-salts-button"
                                >
                                    {loading ? (
                                        <>
                                            <Spinner/>
                                            {__("Changing Salt Keys...", "salt-shaker")}
                                        </>
                                    ) : (
                                        __("Change Salt Keys Now", "salt-shaker")
                                    )}
                                </Button>
                            </FlexItem>
                        </Flex>
                    </div>
                </CardBody>
            </Card>

            {showConfirmModal && (
                <Modal
                    title={__("Confirm Salt Key Change", "salt-shaker")}
                    onRequestClose={closeConfirmModal}
                    className="salt-shaker-confirm-modal"
                >
                    <div className="modal-content">
                        <p>
                            {__(
                                "Are you sure you want to change your WordPress salt keys?",
                                "salt-shaker"
                            )}
                        </p>
                        <p className="modal-info">
                            {__(
                                "This will log out all users (including you) and require everyone to log in again.",
                                "salt-shaker"
                            )}
                        </p>
                        <div className="modal-actions">
                            <Button variant="secondary" onClick={closeConfirmModal}>
                                {__("Cancel", "salt-shaker")}
                            </Button>
                            <Button
                                variant="primary"
                                onClick={() => {
                                    closeConfirmModal();
                                    handleChangeSalts();
                                }}
                                disabled={!isConfigWritable}
                            >
                                {__("Yes, Change Salt Keys", "salt-shaker")}
                            </Button>
                        </div>
                    </div>
                </Modal>
            )}
        </div>
    );
}

export default ImmediateChange;
