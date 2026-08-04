import ProfileController from './ProfileController'
import SecurityController from './SecurityController'
import NotificationsController from './NotificationsController'
import BillingController from './BillingController'
import PlaceholderController from './PlaceholderController'
const Settings = {
    ProfileController: Object.assign(ProfileController, ProfileController),
SecurityController: Object.assign(SecurityController, SecurityController),
NotificationsController: Object.assign(NotificationsController, NotificationsController),
BillingController: Object.assign(BillingController, BillingController),
PlaceholderController: Object.assign(PlaceholderController, PlaceholderController),
}

export default Settings