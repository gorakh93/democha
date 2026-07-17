{{-- resources/views/privacy.blade.php --}}

{{-- @include('header') --}}

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Privacy Policy - Spend It Wisely</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    {{-- Add your CSS here --}}
    <link rel="stylesheet" href="{{ asset('css/style.css') }}">
</head>
<body>

    <header>
        <div class="container">
            <h1>Spend It Wisely</h1>
            <nav>
                <ul>
                    <li><a href="{{ url('/') }}">Home</a></li>
                    <li><a href="{{ url('/') }}">About</a></li>
                    <li><a href="{{ url('/') }}">Contact</a></li>
                </ul>
            </nav>
        </div>
    </header>

    <main>
        <div class="section_premiun_store">
            <div class="premiun_store_box">
                <h2 class="heading">Privacy Policy</h2>
                <div class="premium_child">
                    {{-- Your Privacy Policy Content --}}


                    <div class="section_premiun_store">
    <div class="premiun_store_box">
          <h2 class="heading">Privacy Policy</h2>
          <div class="premium_child">

            <p> <strong>*Introduction : </strong>
At *Spend It Wisely, we are committed to protecting and respecting your privacy. This Privacy Policy explains how we collect, use, disclose, and safeguard your information , onboard as a client, or use our services to list products.

<br><br>


<strong>1. Information We Collect : </strong>

We collect various types of information to provide and improve our services. The information we collect includes:


- *Personal Information:*<br><br>

 When you onboard as a shopkeeper or service provider, we may collect your name, email address, phone number, business details, and other contact information.
  
- *Business Details:* <br><br>If you are a shopkeeper, we may collect details about your shop, products, prices, and inventory.

- *Service Provider Information:* <br><br>If you list as a local service provider (electrician, plumber, carpenter, etc.), we may collect your professional details, business hours, and service offerings.

- *User Information:* Visitors to the website may provide personal information when contacting service providers or using the services listed on the website.

- *Payment Information:* If you make a payment for services or listings, we may collect payment details (credit card information, etc.), processed through a secure third-party payment processor.

- *Usage Data:* We automatically collect data about how users interact with our website, such as IP addresses, browser types, device information, and website traffic.


<br><br>
<strong>2. How We Use Your Information : </strong>

We use the information we collect for the following purposes:

- To provide and manage our services, including onboarding shopkeepers and service providers, listing their products or services, and facilitating user contact.
- To process payments and orders.
- To improve the user experience on our website.
- To send marketing and promotional messages (if you opt-in).
- To comply with legal obligations, enforce our policies, and protect our rights.

<br><br>
<strong>3. Data Sharing and Disclosure : </strong>

We do not sell or trade your personal information. However, we may share your data in the following circumstances:

- *Service Providers:* We may share information with third-party service providers that help us operate our business (e.g., payment processors, hosting providers, or analytics tools).
  
- *Legal Requirements:* We may disclose your information to comply with legal obligations or respond to lawful requests by public authorities.

- *Business Transfers:* In the event of a merger, acquisition, or sale of assets, your information may be transferred as part of that transaction.
<br><br>

<strong>4. Cookies and Tracking Technologies : </strong>

We use cookies and similar tracking technologies to enhance user experience, analyze website usage, and improve our services. You can adjust your browser settings to manage or block cookies.
<br><br>

<strong>5. Security of Your Information : </strong>

We take reasonable measures to protect your personal data, including using encryption and secure connections. However, no data transmission over the internet is entirely secure, and we cannot guarantee the absolute security of your data.
<br><br>

<strong>6. Your Rights and Choices : </strong>

You have the following rights concerning your personal information:

- *Access and Correction:* You may request access to the information we hold about you and ask us to correct any inaccuracies.
  
- *Deletion:* You can request the deletion of your personal data, subject to certain legal restrictions.

- *Opt-out:* You can opt out of marketing communications by following the unsubscribe instructions in the messages or contacting us directly.
<br><br>

<strong>7. Return and Refund Policy : </strong>

Spend It Wisely acts as a platform for shopkeepers and service providers to list their products and services. The return, refund, and delivery process is directly managed by the respective shopkeepers or service providers listed on our website.

In case of any issues related to product returns or refunds, users must directly contact the shopkeeper or service provider from whom they made the purchase or service request. Spend It Wisely does not handle or manage any returns, refunds, or exchanges. Users are encouraged to review the shopkeeper's return policy and contact them for any concerns or issues.
<br><br>

<strong>8. Links to Third-Party Websites : </strong>

Our website may contain links to third-party websites. We are not responsible for the privacy practices of these websites. We encourage you to review their privacy policies before providing any personal information.
<br><br>
<strong>9. Changes to This Privacy Policy : </strong>
We may update this Privacy Policy from time to time. Any changes will be posted on this page with the updated effective date. Please review this Privacy Policy periodically.
<br><br>



</p>

          </div>
    </div>
</div>
                    






                    
                    {{-- Or directly paste your content here as you did --}}
                </div>
            </div>
        </div>
    </main>

    <footer>
        <div class="container">
            <p>&copy; {{ date('Y') }} Spend It Wisely. All rights reserved.</p>
            <p>
                <a href="{{ url('/privacy') }}">Privacy Policy</a> | 
                <a href="{{ url('/terms') }}">Terms of Service</a>
            </p>
        </div>
    </footer>

</body>
</html>

{{-- @include('footer') --}}
