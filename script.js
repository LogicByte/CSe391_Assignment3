const appointmentForm = document.querySelector('form');
const appointmentDate = document.querySelector('#appointment-date');
const mechanicDropdown = document.querySelector('#mechanic-dropdown');
let activeMechanicsRequest;

function getLocalDateString() {
    const now = new Date();
    return [
        now.getFullYear(),
        String(now.getMonth() + 1).padStart(2, '0'),
        String(now.getDate()).padStart(2, '0'),
    ].join('-');
}

async function loadMechanics() {
    if (!mechanicDropdown) {
        return;
    }

    const inputDate = appointmentDate?.value || '';
    const date = /^\d{4}-\d{2}-\d{2}$/.test(inputDate)
        ? inputDate
        : getLocalDateString();

    if (activeMechanicsRequest) {
        activeMechanicsRequest.abort();
    }

    activeMechanicsRequest = new AbortController();
    mechanicDropdown.disabled = true;
    mechanicDropdown.innerHTML = '<option value="" disabled selected>Choose an available mechanic</option>';

    try {
        const response = await fetch('api_mechanics.php?date=' + date, {
            signal: activeMechanicsRequest.signal,
        });
        if (!response.ok) {
            throw new Error('Network response was not ok.');
        }

        const data = await response.json();
        if (!Array.isArray(data)) {
            throw new Error('Mechanics response was invalid.');
        }

        mechanicDropdown.innerHTML = '<option value="" disabled selected>Choose an available mechanic</option>';
        for (const mechanic of data) {
            if (
                !Number.isInteger(mechanic.id)
                || typeof mechanic.name !== 'string'
                || typeof mechanic.specialty !== 'string'
                || !Number.isInteger(mechanic.slots)
            ) {
                throw new Error('Mechanics response was invalid.');
            }

            const slots = Math.max(0, mechanic.slots);
            const option = document.createElement('option');
            option.value = mechanic.id;
            option.textContent = slots === 0
                ? `${mechanic.name} (${mechanic.specialty}) - (FULL)`
                : `${mechanic.name} (${mechanic.specialty}) - ${slots} slots available`;
            option.disabled = slots === 0;
            mechanicDropdown.appendChild(option);
        }

        mechanicDropdown.disabled = false;
    } catch (error) {
        if (error.name === 'AbortError') {
            return;
        }

        console.error('Failed to load mechanics');
    }
}

if (appointmentDate) {
    const today = getLocalDateString();
    appointmentDate.min = today;
    appointmentDate.value = today;
    appointmentDate.addEventListener('change', loadMechanics);
    loadMechanics();
}

if (appointmentForm) {
    appointmentForm.noValidate = true;

    const rules = [
        {
            field: document.querySelector('#client_name'),
            message: 'Enter your name using at least 3 characters.',
            isValid: (value) => value.trim().length >= 3,
        },
        {
            field: document.querySelector('#address'),
            message: 'Enter your address.',
            isValid: (value) => value.trim().length > 0,
        },
        {
            field: document.querySelector('#phone'),
            message: 'Enter 10 to 15 digits, with no spaces or symbols.',
            isValid: (value) => /^\d{10,15}$/.test(value.trim()),
        },
        {
            field: document.querySelector('#license_no'),
            message: 'Enter a license number using letters and numbers only.',
            isValid: (value) => /^[a-z0-9]+$/i.test(value.trim()),
        },
        {
            field: document.querySelector('#engine_no'),
            message: 'Enter an engine number using digits only.',
            isValid: (value) => /^\d+$/.test(value.trim()),
        },
        {
            field: appointmentDate,
            message: 'Choose today or a future appointment date.',
            isValid: (value) => value !== '' && value >= getLocalDateString(),
        },
        {
            field: document.querySelector('#mechanic-dropdown'),
            message: 'Select an available mechanic.',
            isValid: (value) => value !== '',
        },
    ].filter((rule) => rule.field);

    function clearError(field) {
        const error = document.getElementById(`${field.id}-error`);
        if (error) {
            error.remove();
        }

        field.classList.remove('input-error');
        field.removeAttribute('aria-invalid');
        field.removeAttribute('aria-describedby');
    }

    function showError(field, message) {
        clearError(field);

        const error = document.createElement('small');
        error.id = `${field.id}-error`;
        error.className = 'field-error';
        error.textContent = message;

        field.classList.add('input-error');
        field.setAttribute('aria-invalid', 'true');
        field.setAttribute('aria-describedby', error.id);
        field.insertAdjacentElement('afterend', error);
    }

    for (const { field } of rules) {
        const eventName = field.tagName === 'SELECT' ? 'change' : 'input';
        field.addEventListener(eventName, () => clearError(field));
        if (field.type === 'date') {
            field.addEventListener('change', () => clearError(field));
        }
    }

    appointmentForm.addEventListener('submit', (event) => {
        let firstInvalidField = null;

        for (const { field, message, isValid } of rules) {
            if (!isValid(field.value)) {
                showError(field, message);
                if (!firstInvalidField) {
                    firstInvalidField = field;
                }
            } else {
                clearError(field);
            }
        }

        if (firstInvalidField) {
            event.preventDefault();
            firstInvalidField.focus();
        }
    });
}
