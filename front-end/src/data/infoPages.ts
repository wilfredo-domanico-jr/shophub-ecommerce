export interface InfoSection {
  heading: string;
  body: string;
}

export interface FaqItem {
  q: string;
  a: string;
}

export interface InfoPageContent {
  title: string;
  tagline: string;
  sections?: InfoSection[];
  faqs?: FaqItem[];
}

export const infoPages: Record<string, InfoPageContent> = {
  "help-center": {
    title: "Help Center",
    tagline: "Answers to the questions we get asked the most.",
    faqs: [
      {
        q: "How do I place an order?",
        a: "Browse or search for a product, add it to your cart, then click \"Proceed to Checkout.\" You don't need an account — just enter your name, email, phone number, and shipping address, and you're set.",
      },
      {
        q: "How do I track my order?",
        a: "Click \"Track Order\" in the header or footer, then enter the order number from your confirmation email along with the email address you used at checkout.",
      },
      {
        q: "What payment methods do you accept?",
        a: "Right now we support Cash on Delivery for every order. We're working on adding card and e-wallet payments soon.",
      },
      {
        q: "Can I change or cancel my order after placing it?",
        a: "Since orders start processing quickly, reach out to our support team as soon as possible via help@shophub.test and we'll do our best to help before it ships.",
      },
      {
        q: "Do you offer international shipping?",
        a: "At the moment we only ship within the Philippines. We're hoping to expand to more countries in the future.",
      },
    ],
  },
  "returns-refunds": {
    title: "Returns & Refunds",
    tagline: "Not quite right? Here's how returns work.",
    sections: [
      {
        heading: "30-Day Return Window",
        body: "If you're not happy with your purchase, you can request a return within 30 days of delivery. Items must be unused, in their original packaging, and include any tags or accessories that came with them.",
      },
      {
        heading: "How to Start a Return",
        body: "Email help@shophub.test with your order number and the reason for the return. Our team will send you return instructions and a prepaid shipping label within one business day.",
      },
      {
        heading: "Refund Timeline",
        body: "Once we receive and inspect your returned item, refunds are issued to your original payment method within 5–7 business days. Cash on Delivery orders are refunded via bank transfer or GCash.",
      },
      {
        heading: "Non-Returnable Items",
        body: "For hygiene reasons, we can't accept returns on personal care items (like beauty products) once opened. Sale items marked \"Final Sale\" are also not eligible for return.",
      },
    ],
  },
  "shipping-info": {
    title: "Shipping Info",
    tagline: "Everything you need to know about getting your order.",
    sections: [
      {
        heading: "Processing Time",
        body: "Orders are typically processed and handed to our courier partners within 1–2 business days of being placed.",
      },
      {
        heading: "Delivery Estimates",
        body: "Metro areas usually receive orders within 2–4 business days. Provincial addresses may take 5–7 business days depending on courier coverage.",
      },
      {
        heading: "Shipping Fees",
        body: "We currently offer free shipping on all orders, no minimum spend required — while ShopHub is in its early days, we're covering the cost as a thank-you to our first customers.",
      },
      {
        heading: "Order Tracking",
        body: "Every order includes a unique order number. Use the \"Track Order\" link in the header or footer along with the email you checked out with to see the latest status any time.",
      },
    ],
  },
  "our-story": {
    title: "Our Story",
    tagline: "How a small idea turned into ShopHub.",
    sections: [
      {
        heading: "Where It Started",
        body: "ShopHub began as a simple idea: online shopping should feel fast, friendly, and a little bit fun. We started small — a handful of product categories and a lot of late nights — and we've been building ever since.",
      },
      {
        heading: "What We Believe",
        body: "We think great e-commerce comes down to three things: products people actually want, prices that feel fair, and a checkout experience that doesn't get in the way. Everything we build starts from there.",
      },
      {
        heading: "Where We're Headed",
        body: "We're just getting started. Expect more categories, more ways to pay, and more features designed around what our customers actually ask for — because that's how ShopHub grows.",
      },
    ],
  },
  careers: {
    title: "Careers",
    tagline: "Help us build the next version of ShopHub.",
    sections: [
      {
        heading: "Current Openings",
        body: "We don't have any open positions listed right now — but ShopHub is growing fast, and that changes often. Check back soon, or send us your resume anyway.",
      },
      {
        heading: "What We Look For",
        body: "We're a small, product-obsessed team. We value curiosity, ownership, and people who care about the small details that make a store feel trustworthy.",
      },
      {
        heading: "Get In Touch",
        body: "Interested in working with us? Send your resume and a short note about why ShopHub excites you to careers@shophub.test — we read every message.",
      },
    ],
  },
  "press-media": {
    title: "Press & Media",
    tagline: "Resources and contacts for press inquiries.",
    sections: [
      {
        heading: "About ShopHub",
        body: "ShopHub is an online marketplace offering electronics, fashion, home goods, and more — built around a simple, no-friction shopping experience.",
      },
      {
        heading: "Media Inquiries",
        body: "For interview requests, quotes, or partnership inquiries, please reach out to press@shophub.test. We aim to respond to press inquiries within two business days.",
      },
      {
        heading: "Brand Assets",
        body: "A press kit with logos and brand guidelines is in the works. In the meantime, email us and we'll be happy to send over what you need directly.",
      },
    ],
  },
  "privacy-policy": {
    title: "Privacy Policy",
    tagline: "How we handle your information.",
    sections: [
      {
        heading: "Information We Collect",
        body: "When you place an order, we collect your name, email address, phone number, and shipping address so we can fulfill and communicate with you about your order. We don't require an account to shop with us.",
      },
      {
        heading: "How We Use Your Information",
        body: "Your information is used solely to process orders, send order confirmations and status updates, and provide customer support if you reach out to us.",
      },
      {
        heading: "Data Sharing",
        body: "We don't sell your personal information to third parties. Order details are only shared with the courier partners needed to deliver your package.",
      },
      {
        heading: "Your Choices",
        body: "You can request a copy of the information we hold about you, or ask us to delete it, at any time by emailing privacy@shophub.test.",
      },
      {
        heading: "Note",
        body: "ShopHub is a portfolio/demo project. This policy is provided for demonstration purposes and does not reflect a live production service.",
      },
    ],
  },
};
