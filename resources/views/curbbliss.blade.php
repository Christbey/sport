<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Subscription Form</title>
    <style>
        :root {
            --primary-color: #2563eb;
            --error-color: #dc2626;
            --success-color: #16a34a;
            --background-color: #f8fafc;
            --text-color: #1e293b;
            --border-color: #e2e8f0;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, 'Helvetica Neue', Arial, sans-serif;
            line-height: 1.6;
            color: var(--text-color);
            background-color: var(--background-color);
            padding: 2rem;
        }

        .container {
            max-width: 800px;
            margin: 0 auto;
            background-color: white;
            border-radius: 12px;
            box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1), 0 2px 4px -1px rgba(0, 0, 0, 0.06);
            padding: 2rem;
        }

        .form-header {
            text-align: center;
            margin-bottom: 2rem;
        }

        .form-header h1 {
            color: var(--primary-color);
            font-size: 1.875rem;
            margin-bottom: 0.5rem;
        }

        .form-group {
            margin-bottom: 1.5rem;
        }

        label {
            display: block;
            font-weight: 600;
            margin-bottom: 0.5rem;
            color: var(--text-color);
        }

        select,
        input {
            width: 100%;
            padding: 0.75rem;
            border: 2px solid var(--border-color);
            border-radius: 6px;
            font-size: 1rem;
            transition: border-color 0.2s ease;
        }

        select:focus,
        input:focus {
            outline: none;
            border-color: var(--primary-color);
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.1);
        }

        select:hover,
        input:hover {
            border-color: var(--primary-color);
        }

        #formWrapper {
            opacity: 0;
            height: 0;
            overflow: hidden;
            transition: opacity 0.3s ease;
        }

        #formWrapper.visible {
            opacity: 1;
            height: auto;
        }

        .submit-button {
            background-color: var(--primary-color);
            color: white;
            padding: 0.75rem 1.5rem;
            border: none;
            border-radius: 6px;
            font-size: 1rem;
            font-weight: 600;
            cursor: pointer;
            width: 100%;
            transition: background-color 0.2s ease;
        }

        .submit-button:hover {
            background-color: #1d4ed8;
        }

        .submit-button:focus {
            outline: none;
            box-shadow: 0 0 0 3px rgba(37, 99, 235, 0.3);
        }

        @media (max-width: 640px) {
            body {
                padding: 1rem;
            }

            .container {
                padding: 1rem;
            }
        }
    </style>
</head>
<body>
<div class="container">
    <div class="form-header">
        <h1>Subscription Service</h1>
        <p>Choose your plan and complete the form below</p>
    </div>

    <div class="form-group">
        <label for="subscription_plan">Choose a Subscription Plan</label>
        <select id="subscription_plan" name="subscription_plan" required>
            <option value="" disabled selected>Select a Subscription Plan</option>
            <option value="bi-weekly-29.99">BI-WEEKLY SUBSCRIPTION SERVICE - $29.99 Monthly</option>
            <option value="bi-weekly-extra-driveway-34.99">BI-WEEKLY SUBSCRIPTION SERVICE PLUS EXTRA LONG DRIVEWAY
                (+50FT) - $34.99 Monthly
            </option>
            <option value="bi-weekly-extra-can-34.99">BI-WEEKLY SUBSCRIPTION SERVICE PLUS EXTRA CAN - $34.99 Monthly
            </option>
            <option value="bi-weekly-extra-driveway-can-39.99">BI-WEEKLY SUBSCRIPTION SERVICE PLUS EXTRA LONG DRIVEWAY
                (+50FT) PLUS EXTRA CAN - $39.99 Monthly
            </option>
            <option value="monthly-39.99">Monthly Subscription - $39.99</option>
            <option value="monthly-extra-driveway-44.99">Monthly Subscription with Extra Long Driveway (+50FT) -
                $44.99
            </option>
            <option value="monthly-extra-can-44.99">Monthly Subscription with Extra Can - $44.99</option>
            <option value="monthly-extra-driveway-can-50.99">Monthly Subscription with Extra Long Driveway (+50FT) plus
                Extra Can - $50.99
            </option>
        </select>
    </div>

    <div id="formWrapper">
        <form id="urableCustomerForm">
            <div class="form-group">
                <label for="name">Name</label>
                <input type="text" id="name" name="name" required>
            </div>

            <div class="form-group">
                <label for="email">Email Address</label>
                <input type="email" id="email" name="email" required>
            </div>

            <div class="form-group">
                <label for="phone_number">Phone Number</label>
                <input type="tel" id="phone_number" name="phone_number" required>
            </div>

            <div class="form-group">
                <label for="can_location">Location of Cans</label>
                <input type="text" id="can_location" name="can_location">
            </div>

            <div class="form-group">
                <label for="pets">Any pets we should know about?</label>
                <input type="text" id="pets" name="pets">
            </div>

            <div class="form-group">
                <label for="gate_code">Gate Code/Garage Code</label>
                <input type="text" id="gate_code" name="gate_code">
            </div>

            <div class="form-group">
                <label for="start_date">Requested Start Service Date</label>
                <input type="date" id="start_date" name="start_date">
            </div>

            <div class="form-group">
                <label for="trash_pickup_day">Trash Can Pickup Day</label>
                <select id="trash_pickup_day" name="trash_pickup_day">
                    <option value="">Select Frequency</option>
                    <option value="weekly">Weekly</option>
                    <option value="bi-weekly">Bi-Weekly</option>
                </select>
            </div>

            <div class="form-group">
                <label for="recycling_pickup_day">Recycling Can Pickup Day</label>
                <select id="recycling_pickup_day" name="recycling_pickup_day">
                    <option value="">Select Frequency</option>
                    <option value="weekly">Weekly</option>
                    <option value="bi-weekly">Bi-Weekly</option>
                </select>
            </div>

            <button type="button" id="submitButton" class="submit-button">Submit</button>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const subscriptionPlanElement = document.getElementById('subscription_plan');
        const formWrapper = document.getElementById('formWrapper');
        const submitButton = document.getElementById('submitButton');

        if (!subscriptionPlanElement || !formWrapper || !submitButton) {
            console.error('Required elements are not found. Check the IDs.');
            return;
        }

        subscriptionPlanElement.addEventListener('change', function () {
            const selectedPlan = subscriptionPlanElement.value.trim();
            formWrapper.style.display = selectedPlan ? 'block' : 'none';
            if (selectedPlan) {
                formWrapper.classList.add('visible');
            } else {
                formWrapper.classList.remove('visible');
            }
        });

        submitButton.addEventListener('click', function () {
            const subscriptionPlan = subscriptionPlanElement.value.trim();

            if (!subscriptionPlan) {
                alert('Please select a subscription plan.');
                subscriptionPlanElement.focus();
                return;
            }

            const payload = {
                type: "person",
                status: "new",
                firstName: document.getElementById('name').value.trim(),
                phoneNumbers: [
                    {
                        label: "Mobile",
                        value: document.getElementById('phone_number').value.trim()
                    }
                ],
                emails: [
                    {
                        label: "Home",
                        value: document.getElementById('email').value.trim()
                    }
                ],
                subscription: subscriptionPlan,
                notes: "Customer submitted via web form."
            };

            console.log('Payload to be sent:', payload);

            fetch('https://app.urable.com/api/v1/customers', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Authorization': 'Bearer YOUR_AUTH_TOKEN'
                },
                body: JSON.stringify(payload)
            })
                .then(response => {
                    if (response.ok) {
                        window.location.href = 'https://app.urable.com/virtual-shop/dGamc3EX2Ci9WTxPc3Vv';
                    } else {
                        return response.json().then(data => {
                            console.error('API Error:', data);
                            alert('Error creating customer: ' + (data.message || 'Please try again.'));
                        });
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    alert('An unexpected error occurred.');
                });
        });
    });
</script>
</body>
</html>