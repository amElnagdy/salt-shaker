import { __ } from "@wordpress/i18n";
import { Card, CardBody } from "@wordpress/components";

function Statistics({ stats }) {
    const formatDuration = (ms) => {
        if (ms < 1000) {
            return `${ms}ms`;
        }
        return `${(ms / 1000).toFixed(2)}s`;
    };

    const getTimeAgo = (lastRotation) => {
        if (!lastRotation || !lastRotation.rotation_time) {
            return __("Never", "salt-shaker");
        }

        const rotationDate = new Date(lastRotation.rotation_time.replace(" ", "T"));
        const now = new Date();
        const diffMs = now - rotationDate;
        const diffDays = Math.floor(diffMs / (1000 * 60 * 60 * 24));

        if (diffDays === 0) {
            const diffHours = Math.floor(diffMs / (1000 * 60 * 60));
            if (diffHours === 0) {
                const diffMinutes = Math.floor(diffMs / (1000 * 60));
                return diffMinutes + " " + __("minutes ago", "salt-shaker");
            }
            return diffHours + " " + __("hours ago", "salt-shaker");
        } else if (diffDays === 1) {
            return __("1 day ago", "salt-shaker");
        }
        return diffDays + " " + __("days ago", "salt-shaker");
    };

    return (
        <Card className="salt-shaker-stats-card">
            <CardBody>
                <div className="salt-shaker-stats-grid">
                    <div className="salt-shaker-stat">
                        <div className="stat-label">{__("Total Rotations", "salt-shaker")}</div>
                        <div className="stat-value">{stats.total_rotations}</div>
                    </div>
                    <div className="salt-shaker-stat">
                        <div className="stat-label">{__("Success Rate", "salt-shaker")}</div>
                        <div className="stat-value">{stats.success_rate}%</div>
                    </div>
                    <div className="salt-shaker-stat">
                        <div className="stat-label">{__("Failed (30 days)", "salt-shaker")}</div>
                        <div className="stat-value stat-failed">{stats.failed_30_days}</div>
                    </div>
                    <div className="salt-shaker-stat">
                        <div className="stat-label">{__("Avg Duration", "salt-shaker")}</div>
                        <div className="stat-value">{formatDuration(stats.avg_duration_ms)}</div>
                    </div>
                    <div className="salt-shaker-stat">
                        <div className="stat-label">{__("Last Rotation", "salt-shaker")}</div>
                        <div className="stat-value">{getTimeAgo(stats.last_rotation)}</div>
                    </div>
                </div>
            </CardBody>
        </Card>
    );
}

export default Statistics;
