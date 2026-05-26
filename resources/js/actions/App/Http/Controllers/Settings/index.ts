import ProfileController from './ProfileController'
import SecurityController from './SecurityController'
import BillingController from './BillingController'
const Settings = {
    ProfileController: Object.assign(ProfileController, ProfileController),
SecurityController: Object.assign(SecurityController, SecurityController),
BillingController: Object.assign(BillingController, BillingController),
}

export default Settings