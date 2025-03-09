import {__} from "@wordpress/i18n";
import {Button, Card, CardBody, CardFooter, CardHeader, Icon} from '@wordpress/components';

function ProFeatures() {
    const features = [
        {
            icon: 'shield',
            title: __('Premium 24/7 Support', 'salt-shaker'),
            description: __('Get expert help anytime with our dedicated support team.', 'salt-shaker'),
        },
        {
            icon: 'clock',
            title: __('Smart Scheduling', 'salt-shaker'),
            description: __('Choose the perfect time for updates - when your users are least active. No more unexpected logouts!', 'salt-shaker'),
        },
        {
            icon: 'email',
            title: __('Instant Notifications', 'salt-shaker'),
            description: __('Get immediate email alerts when salt keys are updated. Stay informed and in control.', 'salt-shaker')
        },
        {
            icon: 'admin-users',
            title: __('Custom Email Alerts', 'salt-shaker'),
            description: __('Send notifications to your preferred email address. Perfect for team management.', 'salt-shaker')
        },
        {
            icon: 'bell',
            title: __('Smart Reminders', 'salt-shaker'),
            description: __('Never miss an update with intelligent reminders when it\'s time to rotate your keys.', 'salt-shaker')
        }
    ];

    return (
        <div className="salt-shaker-pro-features">
            <Card>
                <CardHeader>
                    <div className="salt-shaker-pro-header">
                        <h2>{__('Unlock Premium Features', 'salt-shaker')}</h2>
                        <span className="salt-shaker-pro-badge">{__('PRO', 'salt-shaker')}</span>
                    </div>
                </CardHeader>
                <CardBody>
                    <div className="salt-shaker-features-grid">
                        {features.map((feature, index) => (
                            <div
                                key={index}
                                className={`salt-shaker-feature-item ${feature.highlight ? 'feature-highlight' : ''}`}
                            >
                                <div className="feature-icon">
                                    <Icon icon={feature.icon}/>
                                </div>
                                <div className="feature-content">
                                    <h3>{feature.title}</h3>
                                    <p>{feature.description}</p>
                                </div>
                            </div>
                        ))}
                    </div>
                </CardBody>
                <CardFooter>
                    <div className="salt-shaker-pro-cta" id="salt-shaker-pro-features">
                        <div className="salt-shaker-pro-offer">
                            <span className="offer-badge">{__('LIMITED TIME OFFER', 'salt-shaker')}</span>
                            <p className="salt-shaker-pro-discount">
                                {__('Get 50% off Salt Shaker PRO!', 'salt-shaker')}
                                <br/>
                                <span className="pricing-info">
                                    <span className="original-price">${__('9.99', 'salt-shaker')}</span>
                                    <span className="discounted-price">${__('4.99', 'salt-shaker')}</span>
                                    <span className="period">/{__('year', 'salt-shaker')}</span>
                                </span>
                                <br/>
                                <span className="coupon-code">SALTSHAKERPRO</span>
                            </p>
                        </div>
                        <Button
                            isPrimary
                            href="/wp-admin/tools.php?page=salt_shaker-pricing"
                            className="upgrade-button"
                        >
                            {__('Upgrade Now', 'salt-shaker')}
                        </Button>
                    </div>
                </CardFooter>
            </Card>
        </div>
    );
}

export default ProFeatures;
