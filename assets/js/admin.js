import {createRoot, StrictMode, useEffect, useState,} from "@wordpress/element";
import {__} from "@wordpress/i18n";
import {Card, CardBody, CardHeader} from "@wordpress/components";
import CurrentSalts from "./components/CurrentSalts";
import ScheduledChange from "./components/ScheduledChange";
import ImmediateChange from "./components/ImmediateChange";
import RotationHistory from "./components/RotationHistory";
import ProFeatures from "./components/ProFeatures";

function SaltShakerAdmin() {
    const [isConfigWritable, setIsConfigWritable] = useState(true);

    useEffect(() => {
        // Get initial settings
        fetch(saltShakerData.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "salt_shaker_get_settings",
                nonce: saltShakerData.nonce,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    setIsConfigWritable(data.data.isConfigWritable);
                }
            })
            .catch((error) => {
                console.error("Error fetching settings:", error);
                setIsConfigWritable(false);
            });
    }, []);

    return (
        <div className="salt-shaker-admin">
            <div className="salt-shaker-grid">
                <div className="salt-shaker-main">
                    <Card>
                        <CardHeader>
                            <h2>{__("Salt Shaker Settings", "salt-shaker")}</h2>
                        </CardHeader>
                        <CardBody>
                            <p
                                dangerouslySetInnerHTML={{
                                    __html: __(
                                        "WordPress salt keys or security keys are codes that help protect important information on your website. They make it harder for hackers to access your website by making passwords more complex. <br /> You don't need to remember these codes, Salt Shaker plugin takes care of generating the codes directly from WordPress API.",
                                        "salt-shaker"
                                    ),
                                }}
                                className="description"
                            />

                            <CurrentSalts isConfigWritable={isConfigWritable}/>
                            <ScheduledChange isConfigWritable={isConfigWritable}/>
                            <ImmediateChange isConfigWritable={isConfigWritable}/>
                            <RotationHistory />

                            <p className="rate-plugin">
                                {__("Do you find this plugin useful? Please", "salt-shaker")}{" "}
                                <a
                                    href="https://wordpress.org/support/plugin/salt-shaker/reviews/#new-post"
                                    target="_blank"
                                    rel="noopener noreferrer"
                                >
                                    {__("Rate it", "salt-shaker")}
                                </a>{" "}
                                {__("on WordPress.org. BIG Thanks in advance!", "salt-shaker")}
                            </p>
                        </CardBody>
                    </Card>
                </div>
                <div className="salt-shaker-sidebar">
                    <ProFeatures/>
                </div>
            </div>
        </div>
    );
}

window.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("salt-shaker-settings");

    if (container) {
        try {
            const root = createRoot(container);
            root.render(
                <StrictMode>
                    <SaltShakerAdmin/>
                </StrictMode>
            );
        } catch (error) {
            console.error("Failed to render Salt Shaker admin:", error);
        }
    } else {
        console.error("Could not find salt-shaker-settings container element");
    }
});

export default SaltShakerAdmin;
