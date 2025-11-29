import { __ } from "@wordpress/i18n";
import { SelectControl, Button } from "@wordpress/components";

function LogFilters({ filters, onFilterChange }) {
    const handleFilterChange = (key, value) => {
        onFilterChange({
            ...filters,
            [key]: value,
        });
    };

    const handleReset = () => {
        onFilterChange({
            status: "",
            method: "",
            user_id: "",
        });
    };

    return (
        <div className="salt-shaker-filters">
            <SelectControl
                label={__("Status", "salt-shaker")}
                value={filters.status}
                options={[
                    { label: __("All Statuses", "salt-shaker"), value: "" },
                    { label: __("Success", "salt-shaker"), value: "success" },
                    { label: __("Failed", "salt-shaker"), value: "failed" },
                ]}
                onChange={(value) => handleFilterChange("status", value)}
            />
            <SelectControl
                label={__("Method", "salt-shaker")}
                value={filters.method}
                options={[
                    { label: __("All Methods", "salt-shaker"), value: "" },
                    { label: __("Manual", "salt-shaker"), value: "manual" },
                    { label: __("Scheduled", "salt-shaker"), value: "scheduled" },
                    { label: __("CLI", "salt-shaker"), value: "cli" },
                    { label: __("API", "salt-shaker"), value: "api" },
                ]}
                onChange={(value) => handleFilterChange("method", value)}
            />
            <Button variant="secondary" onClick={handleReset}>
                {__("Reset Filters", "salt-shaker")}
            </Button>
        </div>
    );
}

export default LogFilters;
