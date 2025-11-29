import { createRoot, StrictMode, useState, useEffect } from "@wordpress/element";
import { __, sprintf } from "@wordpress/i18n";
import {
    Card,
    CardBody,
    CardHeader,
    Button,
    SelectControl,
    TextControl,
    Modal,
    Spinner,
    Notice,
    __experimentalDivider as Divider,
} from "@wordpress/components";

import Statistics from "./components/AuditStatistics";
import LogTable from "./components/AuditLogTable";
import LogFilters from "./components/AuditFilters";
import AuditSettings from "./components/AuditSettings";

function AuditLog() {
    const [logs, setLogs] = useState([]);
    const [stats, setStats] = useState(null);
    const [loading, setLoading] = useState(true);
    const [error, setError] = useState(null);
    const [page, setPage] = useState(1);
    const [totalPages, setTotalPages] = useState(1);
    const [filters, setFilters] = useState({
        status: "",
        method: "",
        user_id: "",
    });

    // Fetch stats
    const fetchStats = () => {
        fetch(saltShakerAuditData.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "salt_shaker_get_audit_stats",
                nonce: saltShakerAuditData.nonce,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    setStats(data.data);
                }
            })
            .catch((err) => {
                console.error("Error fetching stats:", err);
            });
    };

    // Fetch logs
    const fetchLogs = () => {
        setLoading(true);
        setError(null);

        fetch(saltShakerAuditData.ajaxUrl, {
            method: "POST",
            headers: {
                "Content-Type": "application/x-www-form-urlencoded",
            },
            body: new URLSearchParams({
                action: "salt_shaker_get_audit_logs",
                nonce: saltShakerAuditData.nonce,
                page: page,
                per_page: 20,
                ...filters,
            }),
        })
            .then((response) => response.json())
            .then((data) => {
                if (data.success) {
                    setLogs(data.data.logs);
                    setTotalPages(data.data.total_pages);
                } else {
                    setError(data.data || __("Failed to load audit logs", "salt-shaker"));
                }
            })
            .catch((err) => {
                setError(__("Network error while loading audit logs", "salt-shaker"));
                console.error("Error fetching logs:", err);
            })
            .finally(() => {
                setLoading(false);
            });
    };

    // Initial load
    useEffect(() => {
        fetchStats();
        fetchLogs();
    }, [page, filters]);

    const handleFilterChange = (newFilters) => {
        setFilters(newFilters);
        setPage(1); // Reset to first page when filters change
    };

    const handleExport = (format) => {
        const formData = new URLSearchParams({
            action: "salt_shaker_export_audit_logs",
            nonce: saltShakerAuditData.nonce,
            format: format,
            ...filters,
        });

        // Create a form and submit it to trigger download
        const form = document.createElement("form");
        form.method = "POST";
        form.action = saltShakerAuditData.ajaxUrl;
        form.style.display = "none";

        formData.forEach((value, key) => {
            const input = document.createElement("input");
            input.name = key;
            input.value = value;
            form.appendChild(input);
        });

        document.body.appendChild(form);
        form.submit();
        document.body.removeChild(form);
    };

    return (
        <div className="salt-shaker-audit-log">
            <div className="salt-shaker-audit-header">
                <h1>{__("Salt Shaker - Audit Log", "salt-shaker")}</h1>
                <div className="salt-shaker-audit-actions">
                    <Button
                        variant="secondary"
                        onClick={() => handleExport("csv")}
                    >
                        {__("Export CSV", "salt-shaker")}
                    </Button>
                    <Button
                        variant="secondary"
                        onClick={() => handleExport("json")}
                    >
                        {__("Export JSON", "salt-shaker")}
                    </Button>
                </div>
            </div>

            {error && (
                <Notice status="error" isDismissible={false}>
                    {error}
                </Notice>
            )}

            {stats && <Statistics stats={stats} />}

            <Divider />

            <Card>
                <CardHeader>
                    <h2>{__("Rotation History", "salt-shaker")}</h2>
                </CardHeader>
                <CardBody>
                    <LogFilters filters={filters} onFilterChange={handleFilterChange} />

                    {loading ? (
                        <div className="salt-shaker-loading">
                            <Spinner />
                            <p>{__("Loading audit logs...", "salt-shaker")}</p>
                        </div>
                    ) : (
                        <LogTable
                            logs={logs}
                            page={page}
                            totalPages={totalPages}
                            onPageChange={setPage}
                        />
                    )}
                </CardBody>
            </Card>

            <Divider />

            <AuditSettings onSettingsSaved={fetchStats} />
        </div>
    );
}

window.addEventListener("DOMContentLoaded", () => {
    const container = document.getElementById("salt-shaker-audit-log");

    if (container) {
        try {
            const root = createRoot(container);
            root.render(
                <StrictMode>
                    <AuditLog />
                </StrictMode>
            );
        } catch (error) {
            console.error("Failed to render Salt Shaker audit log:", error);
        }
    }
});

export default AuditLog;
