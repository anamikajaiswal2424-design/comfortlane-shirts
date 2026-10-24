<?php
require __DIR__ . '/includes/init.php';
if (!cartCount()) {
    redirect('cart.php');
}

$errors = [];
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!validCsrf()) {
        $errors[] = 'Your session expired. Please try again.';
    }
    $address = [
        'name' => trim((string) ($_POST['name'] ?? '')),
        'phone' => preg_replace('/\D+/', '', (string) ($_POST['phone'] ?? '')),
        'pincode' => preg_replace('/\D+/', '', (string) ($_POST['pincode'] ?? '')),
        'city' => trim((string) ($_POST['city'] ?? '')),
        'state' => trim((string) ($_POST['state'] ?? '')),
        'house' => trim((string) ($_POST['house'] ?? '')),
        'area' => trim((string) ($_POST['area'] ?? '')),
        'landmark' => trim((string) ($_POST['landmark'] ?? '')),
        'type' => in_array($_POST['type'] ?? '', ['Home', 'Work'], true) ? $_POST['type'] : 'Home',
    ];
    if (strlen($address['name']) < 3) $errors[] = 'Please enter your full name.';
    if (!preg_match('/^[6-9][0-9]{9}$/', $address['phone'])) $errors[] = 'Enter a valid 10-digit mobile number.';
    if (!preg_match('/^[0-9]{6}$/', $address['pincode'])) $errors[] = 'Enter a valid 6-digit pincode.';
    if ($address['city'] === '' || $address['state'] === '' || $address['house'] === '' || $address['area'] === '') $errors[] = 'Please complete all required address fields.';

    if (!$errors) {
        $_SESSION['address'] = $address;
        unset($_SESSION['payment']);
        redirect('summary.php');
    }
}

$saved = $_SESSION['address'] ?? [];
$field = static fn(string $key, string $default = ''): string => e((string) ($_POST[$key] ?? $saved[$key] ?? $default));
$pageTitle = 'Add delivery address — ComfyNest';
$layout = 'back';
$headerTitle = 'ADD DELIVERY ADDRESS';
$backHref = 'cart.php';
$pageClass = 'checkout-page address-page';
$checkoutStep = 2;
require __DIR__ . '/includes/header.php';
require __DIR__ . '/includes/checkout-progress.php';
?>

<form class="checkout-form" method="post" action="address.php" novalidate>
    <?= csrfField() ?>
    <div class="form-heading"><span class="heading-icon">⌖</span><div><small>WHERE SHOULD WE SEND IT?</small><h1>Delivery address</h1></div></div>
    <?php if ($errors): ?><div class="form-errors" role="alert"><strong>Please check these details:</strong><ul><?php foreach ($errors as $error): ?><li><?= e($error) ?></li><?php endforeach; ?></ul></div><?php endif; ?>

    <label class="floating-field"><input name="name" value="<?= $field('name') ?>" placeholder=" " autocomplete="name" required><span>Full name</span></label>
    <label class="floating-field"><input name="phone" value="<?= $field('phone') ?>" placeholder=" " inputmode="numeric" maxlength="10" autocomplete="tel" required><span>Mobile number</span></label>
    <label class="floating-field"><input name="pincode" value="<?= $field('pincode') ?>" placeholder=" " inputmode="numeric" maxlength="6" autocomplete="postal-code" required><span>Pincode</span></label>
    <div class="field-pair">
        <label class="floating-field"><input name="city" value="<?= $field('city') ?>" placeholder=" " autocomplete="address-level2" required><span>City</span></label>
        <label class="floating-field select-field"><select name="state" autocomplete="address-level1" required>
            <option value="">Select state</option>
            <?php foreach (['Andhra Pradesh','Delhi','Gujarat','Karnataka','Madhya Pradesh','Maharashtra','Rajasthan','Tamil Nadu','Telangana','Uttar Pradesh','West Bengal'] as $state): ?><option value="<?= e($state) ?>" <?= $field('state', 'Maharashtra') === e($state) ? 'selected' : '' ?>><?= e($state) ?></option><?php endforeach; ?>
        </select><span>State</span></label>
    </div>
    <label class="floating-field"><input name="house" value="<?= $field('house') ?>" placeholder=" " autocomplete="address-line1" required><span>House no., building name</span></label>
    <label class="floating-field"><input name="area" value="<?= $field('area') ?>" placeholder=" " autocomplete="address-line2" required><span>Road name, area, colony</span></label>
    <label class="floating-field"><input name="landmark" value="<?= $field('landmark') ?>" placeholder=" "><span>Landmark (optional)</span></label>

    <fieldset class="address-type"><legend>Save address as</legend>
        <label><input type="radio" name="type" value="Home" <?= $field('type', 'Home') === 'Home' ? 'checked' : '' ?>><span>⌂ Home</span></label>
        <label><input type="radio" name="type" value="Work" <?= $field('type') === 'Work' ? 'checked' : '' ?>><span>▣ Work</span></label>
    </fieldset>

    <div class="form-bottom-space"></div>
    <div class="sticky-action single-action"><button class="button primary" type="submit">Save address & continue <span>→</span></button></div>
</form>

<?php require __DIR__ . '/includes/footer.php'; ?>
