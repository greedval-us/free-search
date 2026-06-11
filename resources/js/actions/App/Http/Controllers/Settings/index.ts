import ProfileController from './ProfileController'
import SecurityController from './SecurityController'
import BillingController from './BillingController'
import PlaceholderController from './PlaceholderController'
const Settings = {
    ProfileController: Object.assign(ProfileController, ProfileController),
SecurityController: Object.assign(SecurityController, SecurityController),
BillingController: Object.assign(BillingController, BillingController),
PlaceholderController: Object.assign(PlaceholderController, PlaceholderController),
}

export default Settings