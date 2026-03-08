<?php
// No PHP code needed before the footer HTML
?>
<!-- Font Awesome for icons (already included in header, but included here for safety) -->
<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

<footer class="footer">
    <div class="footer-container">
        <!-- Main Footer Content -->
        <div class="footer-main">
            <!-- Company Info with Logo Image -->
            <div class="footer-section company-info">
                <div class="footer-logo">
                    <img src="assets/images/wt_logo.png" alt="WiseTechs Logo" class="logo-image">
                    <div class="logo-text">
                        <span class="logo-title"></span>
                        <span class="logo-subtitle"></span>
                    </div>
                </div>
                <div class="footer-tagline">
                    HIGH PERFORMANCE TOLADATA SYSTEM
                </div>
                <p class="company-description">
                    We are a technology company dedicated to providing innovative solutions 
                    for businesses of all sizes. Our team of experts is committed to 
                    delivering high-quality services.
                </p>
            </div>

            <!-- Contact Information -->
            <div class="footer-section contact-info">
                <h3 class="footer-title">
                    <i class="fas fa-info-circle"></i>
                    Contact Information
                </h3>
                <ul class="contact-list">
                    <li>
                        <i class="fas fa-map-marker-alt"></i>
                        <span>No. 75/30, Abdul Hameed Street, Colombo - 12, <br>Sri Lanka.</span>
                    </li>
                    <li>
                        <i class="fas fa-phone"></i>
                        <span>+94 076 222 8185 / +94 077 309 5788</span>
                    </li>
                    <li>
                        <i class="fas fa-envelope"></i>
                        <span>muneer@wisetechs.net</span>
                    </li>
                    <li>
                        <i class="fas fa-globe"></i>
                        <span>www.wisetechs.net</span>
                    </li>
                </ul>
            </div>

            <!-- Quick Links -->
            <div class="footer-section quick-links">
                <h3 class="footer-title">
                    <i class="fas fa-link"></i>
                    Quick Links
                </h3>
                <ul class="links-list">
                    <li><a href="index.php"><i class="fas fa-home"></i> Home</a></li>
                    <li><a href="#"><i class="fas fa-info-circle"></i> About Us</a></li>
                    <li><a href="#"><i class="fas fa-cogs"></i> Services</a></li>
                    <li><a href="#"><i class="fas fa-briefcase"></i> Portfolio</a></li>
                    <li><a href="#"><i class="fas fa-envelope"></i> Contact</a></li>
                </ul>
            </div>
        </div>

        <!-- Copyright Bar -->
        <div class="footer-bottom">
            <div class="copyright">
                <i class="far fa-copyright"></i>
                Copyright 2025 Wise Techs .Net (Pvt) LTD. All Rights Reserved.
            </div>
            <div class="design-credit">
                Designed by: <a href="https://wisetechs.net"><span class="designer">Wise Techs .Net (Pvt) LTD</a></span>
            </div>
        </div>
    </div>
</footer>

<style>
    /* Footer Styles */
    .footer {
        background: linear-gradient(135deg, #0a1929 0%, #1e3a5f 100%);
        color: #ffffff;
        padding: 60px 0 0 0;
        margin-top: 60px;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, sans-serif;
        border-top: 4px solid #f39c12;
    }

    .footer-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 0 20px;
    }

    .footer-main {
        display: grid;
        grid-template-columns: 2fr 1.5fr 1fr;
        gap: 50px;
        margin-bottom: 50px;
    }

    /* Company Info Section with Logo */
    .footer-logo {
        display: flex;
        align-items: center;
        gap: 15px;
        margin-bottom: 20px;
        background: rgba(255, 255, 255, 0.05);
        padding: 15px;
        border-radius: 12px;
        border: 1px solid rgba(243, 156, 18, 0.2);
    }

    .logo-image {
        width: 220px;
        height: 70px;
        object-fit: contain;
         /* Makes white logo if needed, remove if logo is already visible */
        transition: transform 0.3s ease;
    }

    /* If your logo is already visible, remove the filter above and use this instead: */
    /* .logo-image {
        width: 70px;
        height: 70px;
        object-fit: contain;
        transition: transform 0.3s ease;
    } */

    .logo-image:hover {
        transform: scale(1.05);
    }

    .logo-text {
        display: flex;
        flex-direction: column;
    }

    .logo-title {
        font-size: 1.8rem;
        font-weight: 700;
        color: #ffffff;
        text-transform: lowercase;
        line-height: 1.2;
    }

    .logo-subtitle {
        font-size: 0.75rem;
        color: #f39c12;
        letter-spacing: 1px;
        text-transform: uppercase;
        margin-top: 3px;
        font-weight: 500;
    }

    .footer-tagline {
        font-size: 1rem;
        font-weight: 600;
        color: #f39c12;
        letter-spacing: 1px;
        margin: 20px 0 15px;
        text-transform: uppercase;
        border-left: 3px solid #f39c12;
        padding-left: 15px;
    }

    .company-description {
        color: #b0c4de;
        line-height: 1.8;
        font-size: 0.95rem;
        margin-top: 15px;
    }

    /* Footer Titles */
    .footer-title {
        font-size: 1.2rem;
        font-weight: 600;
        margin-bottom: 25px;
        position: relative;
        padding-bottom: 12px;
        color: #fff;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .footer-title i {
        color: #f39c12;
        font-size: 1.1rem;
    }

    .footer-title::after {
        content: '';
        position: absolute;
        bottom: 0;
        left: 0;
        width: 50px;
        height: 3px;
        background: linear-gradient(90deg, #f39c12, #f1c40f);
        border-radius: 2px;
    }

    /* Contact List */
    .contact-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .contact-list li {
        display: flex;
        align-items: flex-start;
        gap: 15px;
        margin-bottom: 18px;
        color: #b0c4de;
        font-size: 0.95rem;
        line-height: 1.6;
        transition: all 0.3s ease;
    }

    .contact-list li:hover {
        transform: translateX(5px);
        color: #ffffff;
    }

    .contact-list li i {
        color: #f39c12;
        font-size: 1.1rem;
        min-width: 20px;
        margin-top: 3px;
    }

    .contact-list li span {
        flex: 1;
        word-break: break-word;
    }

    /* Quick Links */
    .links-list {
        list-style: none;
        padding: 0;
        margin: 0;
    }

    .links-list li {
        margin-bottom: 12px;
    }

    .links-list a {
        color: #b0c4de;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 12px;
        transition: all 0.3s ease;
        font-size: 0.95rem;
    }

    .links-list a i {
        color: #f39c12;
        font-size: 0.9rem;
        width: 20px;
        transition: all 0.3s ease;
    }

    .links-list a:hover {
        color: #f39c12;
        transform: translateX(8px);
    }

    .links-list a:hover i {
        transform: scale(1.1);
    }

    /* Footer Bottom */
    .footer-bottom {
        border-top: 1px solid rgba(255, 255, 255, 0.1);
        padding: 25px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        font-size: 0.9rem;
    }

    .copyright {
        color: #b0c4de;
        display: flex;
        align-items: center;
        gap: 5px;
    }

    .copyright i {
        color: #f39c12;
    }

    .design-credit {
        color: #b0c4de;
    }

    .designer {
        color: #f39c12;
        font-weight: 600;
        position: relative;
        cursor: default;
    }

    .designer:hover {
        text-decoration: underline;
    }

    /* Responsive Design */
    @media (max-width: 1024px) {
        .footer-main {
            grid-template-columns: 1fr 1fr;
            gap: 40px;
        }
        
        .company-info {
            grid-column: span 2;
        }
        
        .logo-title {
            font-size: 1.5rem;
        }
    }

    @media (max-width: 768px) {
        .footer {
            padding: 40px 0 0 0;
        }

        .footer-main {
            grid-template-columns: 1fr;
            gap: 35px;
        }
        
        .company-info {
            grid-column: span 1;
        }

        .footer-bottom {
            flex-direction: column;
            text-align: center;
            gap: 10px;
        }

        .footer-logo {
            flex-direction: column;
            text-align: center;
        }

        .logo-text {
            align-items: center;
        }

        .logo-title {
            font-size: 1.5rem;
        }

        .footer-tagline {
            font-size: 0.9rem;
            text-align: center;
            border-left: none;
            border-top: 2px solid #f39c12;
            padding-top: 10px;
        }
    }

    /* Hover Effects */
    .contact-list li:hover i {
        transform: scale(1.1);
        transition: transform 0.3s ease;
    }

    .logo-image:hover {
        transform: rotate(5deg) scale(1.05);
        transition: transform 0.3s ease;
    }

    /* Print Styles */
    @media print {
        .footer {
            display: none;
        }
    }
</style>

<script>
    // Add current year dynamically
    document.addEventListener('DOMContentLoaded', function() {
        const copyrightElement = document.querySelector('.copyright');
        if (copyrightElement) {
            const currentYear = new Date().getFullYear();
            copyrightElement.innerHTML = `<i class="far fa-copyright"></i> Copyright ${currentYear} Wise Techs .Net (Pvt) LTD. All Rights Reserved.`;
        }
    });
</script>