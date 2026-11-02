import { brandName } from './site';

export const locales = ['fr', 'en'] as const;

export type Locale = (typeof locales)[number];

export type Localized<T = string> = Record<Locale, T>;

const fr = {
    localeName: 'Français',
    brand: {
        tagline: 'Objets et mobilier pour une maison qui vous ressemble',
    },
    demoBanner: 'Boutique fictive : ce site est une démonstration de redirection.io.',
    header: {
        cart: 'Panier',
        language: 'Langue',
        menu: 'Menu',
        searchPlaceholder: 'Rechercher un produit…',
        search: 'Rechercher',
    },
    home: {
        heroTitle: 'La maison, en plus doux',
        heroText: 'Des pièces choisies pour leurs matières, leurs couleurs et leur caractère.',
        heroCta: 'Découvrir le salon',
        categoriesTitle: 'Nos univers',
        featuredTitle: 'Nos coups de cœur',
        storyTitle: 'Sélectionné avec soin',
        storyText: 'Chaque pièce est choisie par notre équipe pour sa qualité de fabrication et sa durabilité. Des matières naturelles, des finitions soignées, et des prix justes.',
        storyCta: 'En savoir plus',
    },
    reassurance: {
        // Keep in sync with src/lib/shipping.ts.
        delivery: 'Livraison offerte dès 100 €',
        returns: 'Retours gratuits sous 30 jours',
        payment: 'Paiement sécurisé',
        support: 'Service client 6 j/7',
    },
    category: {
        productCount: (count: number) => (count > 1 ? `${count} produits` : `${count} produit`),
        filters: 'Filtrer',
        colors: 'Couleur',
        materials: 'Matière',
        sort: 'Trier par',
        sortOptions: {
            featured: 'Pertinence',
            'price-asc': 'Prix croissant',
            'price-desc': 'Prix décroissant',
            name: 'Nom',
        },
        apply: 'Appliquer',
        reset: 'Réinitialiser',
        noResult: 'Aucun produit ne correspond à ces critères.',
        subcategories: 'Catégories',
    },
    pagination: {
        label: 'Pagination',
        previous: 'Précédent',
        next: 'Suivant',
        page: (number: number) => `Page ${number}`,
    },
    product: {
        addToCart: 'Ajouter au panier',
        added: 'Ajouté au panier',
        quantity: 'Quantité',
        inStock: 'En stock, expédié sous 48 h',
        outOfStock: 'Rupture de stock',
        characteristics: 'Caractéristiques',
        dimensions: 'Dimensions',
        dimensionsValue: (width: number, depth: number, height: number) => `L ${width} × P ${depth} × H ${height} cm`,
        colors: 'Couleurs',
        materials: 'Matières',
        related: 'Vous aimerez aussi',
        sale: 'Promo',
    },
    cart: {
        title: 'Votre panier',
        empty: 'Votre panier est vide.',
        continueShopping: 'Continuer mes achats',
        remove: 'Retirer',
        subtotal: 'Sous-total',
        shipping: 'Livraison',
        shippingFree: 'Offerte',
        total: 'Total',
        checkout: 'Passer commande',
    },
    checkout: {
        title: 'Commande',
        contact: 'Coordonnées',
        email: 'E-mail',
        firstName: 'Prénom',
        lastName: 'Nom',
        address: 'Adresse',
        postalCode: 'Code postal',
        city: 'Ville',
        summary: 'Récapitulatif',
        payment: 'Paiement',
        paymentNotice: 'Boutique fictive : aucun paiement ne sera demandé et aucune commande ne sera expédiée.',
        submit: 'Valider la commande',
    },
    orderConfirmation: {
        title: 'Merci pour votre commande',
        text: (orderNumber: string) => `Votre commande n° ${orderNumber} a bien été enregistrée.`,
        notice: 'Rappel : cette boutique est fictive, aucune commande ne sera expédiée.',
        backHome: "Retour à l'accueil",
    },
    search: {
        title: 'Recherche',
        resultsFor: (query: string) => `Résultats pour « ${query} »`,
        noResult: 'Aucun produit ne correspond à votre recherche.',
        prompt: 'Que cherchez-vous ?',
    },
    footer: {
        about: 'La maison',
        help: 'Aide',
        newsletterTitle: 'Restons en contact',
        newsletterText: 'Nouveautés, inspirations et ventes privées, une fois par mois.',
        newsletterPlaceholder: 'Votre e-mail',
        newsletterSubmit: "S'inscrire",
        newsletterThanks: 'Merci ! (Boutique fictive : aucun e-mail ne vous sera envoyé.)',
        copyright: (year: number) => `© ${year} ${brandName}. Boutique fictive.`,
    },
    notFound: {
        title: 'Page introuvable',
        text: "La page que vous cherchez n'existe pas ou a été déplacée.",
        backHome: "Retour à l'accueil",
    },
    languageChooser: {
        title: 'Choisissez votre langue',
    },
    breadcrumb: {
        label: "Fil d'Ariane",
        home: 'Accueil',
    },
};

type Messages = typeof fr;

const en: Messages = {
    localeName: 'English',
    brand: {
        tagline: 'Furniture and objects for a home that feels like you',
    },
    demoBanner: 'Fictional shop: this website is a redirection.io demo.',
    header: {
        cart: 'Cart',
        language: 'Language',
        menu: 'Menu',
        searchPlaceholder: 'Search for a product…',
        search: 'Search',
    },
    home: {
        heroTitle: 'Home, only softer',
        heroText: 'Pieces chosen for their materials, their colors and their character.',
        heroCta: 'Discover the living room',
        categoriesTitle: 'Shop by room',
        featuredTitle: 'Our favorites',
        storyTitle: 'Carefully selected',
        storyText: 'Every piece is chosen by our team for its craftsmanship and durability. Natural materials, careful finishes, and fair prices.',
        storyCta: 'Learn more',
    },
    reassurance: {
        // Keep in sync with src/lib/shipping.ts.
        delivery: 'Free delivery from €100',
        returns: 'Free returns within 30 days',
        payment: 'Secure payment',
        support: 'Customer service 6 days a week',
    },
    category: {
        productCount: (count: number) => (count > 1 ? `${count} products` : `${count} product`),
        filters: 'Filter',
        colors: 'Color',
        materials: 'Material',
        sort: 'Sort by',
        sortOptions: {
            featured: 'Featured',
            'price-asc': 'Price: low to high',
            'price-desc': 'Price: high to low',
            name: 'Name',
        },
        apply: 'Apply',
        reset: 'Reset',
        noResult: 'No product matches these criteria.',
        subcategories: 'Categories',
    },
    pagination: {
        label: 'Pagination',
        previous: 'Previous',
        next: 'Next',
        page: (number: number) => `Page ${number}`,
    },
    product: {
        addToCart: 'Add to cart',
        added: 'Added to cart',
        quantity: 'Quantity',
        inStock: 'In stock, ships within 48 hours',
        outOfStock: 'Out of stock',
        characteristics: 'Details',
        dimensions: 'Dimensions',
        dimensionsValue: (width: number, depth: number, height: number) => `W ${width} × D ${depth} × H ${height} cm`,
        colors: 'Colors',
        materials: 'Materials',
        related: 'You may also like',
        sale: 'Sale',
    },
    cart: {
        title: 'Your cart',
        empty: 'Your cart is empty.',
        continueShopping: 'Continue shopping',
        remove: 'Remove',
        subtotal: 'Subtotal',
        shipping: 'Shipping',
        shippingFree: 'Free',
        total: 'Total',
        checkout: 'Checkout',
    },
    checkout: {
        title: 'Checkout',
        contact: 'Contact details',
        email: 'Email',
        firstName: 'First name',
        lastName: 'Last name',
        address: 'Address',
        postalCode: 'Postal code',
        city: 'City',
        summary: 'Order summary',
        payment: 'Payment',
        paymentNotice: 'Fictional shop: no payment will be requested and no order will be shipped.',
        submit: 'Place order',
    },
    orderConfirmation: {
        title: 'Thank you for your order',
        text: (orderNumber: string) => `Your order #${orderNumber} has been received.`,
        notice: 'Reminder: this shop is fictional, no order will be shipped.',
        backHome: 'Back to home',
    },
    search: {
        title: 'Search',
        resultsFor: (query: string) => `Results for “${query}”`,
        noResult: 'No product matches your search.',
        prompt: 'What are you looking for?',
    },
    footer: {
        about: 'About',
        help: 'Help',
        newsletterTitle: 'Stay in touch',
        newsletterText: 'New arrivals, inspiration and private sales, once a month.',
        newsletterPlaceholder: 'Your email',
        newsletterSubmit: 'Subscribe',
        newsletterThanks: 'Thank you! (Fictional shop: you will not receive any email.)',
        copyright: (year: number) => `© ${year} ${brandName}. Fictional shop.`,
    },
    notFound: {
        title: 'Page not found',
        text: "The page you are looking for doesn't exist or has been moved.",
        backHome: 'Back to home',
    },
    languageChooser: {
        title: 'Choose your language',
    },
    breadcrumb: {
        label: 'Breadcrumb',
        home: 'Home',
    },
};

const messages: Localized<Messages> = { en, fr };

export function translate(locale: Locale): Messages {
    return messages[locale];
}

const intlLocales: Localized = { en: 'en-GB', fr: 'fr-FR' };

export function formatPrice(priceInCents: number, locale: Locale): string {
    const price = new Intl.NumberFormat(intlLocales[locale], { currency: 'EUR', style: 'currency' }).format(priceInCents / 100);

    // French groups thousands with a narrow no-break space, which Inter draws barely 2px wide: "1 290 €" read as "1290 €".
    return price.replaceAll(' ', ' ');
}
