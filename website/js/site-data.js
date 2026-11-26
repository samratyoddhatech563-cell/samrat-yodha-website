/* यह फ़ाइल एडमिन पैनल (samratyodhafoundation.org/admin) अपने आप बनाता है। यहाँ सीधे बदलाव न करें। */
const CONFIG = {
    "whatsapp": "919670930563",
    "facebook": "https://www.facebook.com/share/19tK3Xy29k/",
    "email": "samratyodha563@gmail.com",
    "upi": "",
    "upiName": "Samrat Yodha Foundation",
    "qrImage": "",
    "stats": {
        "pathshalas": 78,
        "children": 1330,
        "districts": 86,
        "centres": 0
    },
    "siteUrl": "https://samratyodhafoundation.org",
    "sampleData": false
};
const PATHSHALAS = [];
const COURSE_TAGS = {
    "बुनियादी पढ़ाई": "Basic studies",
    "प्रतियोगी परीक्षा": "Competitive exams",
    "कंप्यूटर": "Computers",
    "संचार कौशल": "Communication skills",
    "कानूनी जागरूकता": "Legal awareness",
    "व्यावसायिक प्रशिक्षण": "Vocational training"
};
const COURSES = [
    {
        "n": "बुनियादी पढ़ाई",
        "w": "कक्षा 1 से 8 के बच्चे",
        "p": [
            "हिंदी, गणित और अंग्रेज़ी की मज़बूत नींव",
            "रोज़ पढ़ने-लिखने की आदत",
            "स्कूल के गृहकार्य में मदद"
        ],
        "n_en": "Basic studies",
        "w_en": "Children in classes 1 to 8",
        "p_en": [
            "A strong foundation in Hindi, maths and English",
            "A daily habit of reading and writing",
            "Help with school homework"
        ]
    },
    {
        "n": "प्रतियोगी परीक्षा की तैयारी",
        "w": "10वीं और 12वीं के बाद के युवा",
        "p": [
            "सामान्य ज्ञान, गणित और तर्कशक्ति",
            "सरकारी और निजी नौकरियों की जानकारी",
            "नियमित अभ्यास टेस्ट"
        ],
        "n_en": "Competitive exam preparation",
        "w_en": "Young people who have finished class 10 or 12",
        "p_en": [
            "General knowledge, maths and reasoning",
            "Information on government and private jobs",
            "Regular practice tests"
        ]
    },
    {
        "n": "कंप्यूटर और तकनीकी शिक्षा",
        "w": "कक्षा 6 से ऊपर के छात्र और युवा",
        "p": [
            "कंप्यूटर की बुनियादी जानकारी और टाइपिंग",
            "इंटरनेट और ऑनलाइन फ़ॉर्म भरना",
            "डिजिटल सुरक्षा"
        ],
        "n_en": "Computer and technical education",
        "w_en": "Students from class 6 upwards and young people",
        "p_en": [
            "Computer basics and typing",
            "Using the internet and filling in online forms",
            "Digital safety"
        ]
    },
    {
        "n": "संचार कौशल और व्यक्तित्व विकास",
        "w": "किशोर और युवा",
        "p": [
            "साफ़ बोलना और लिखना",
            "इंटरव्यू की तैयारी",
            "आत्मविश्वास और नेतृत्व"
        ],
        "n_en": "Communication skills and personality development",
        "w_en": "Teenagers and young people",
        "p_en": [
            "Speaking and writing clearly",
            "Interview preparation",
            "Confidence and leadership"
        ]
    },
    {
        "n": "कानूनी और संवैधानिक जागरूकता",
        "w": "युवा और अभिभावक",
        "p": [
            "संविधान, मौलिक अधिकार और कर्तव्य",
            "ज्ञापन और जनसुनवाई जैसे शांतिपूर्ण रास्ते",
            "कानूनी सहायता कहाँ मिलती है"
        ],
        "n_en": "Legal and constitutional awareness",
        "w_en": "Young people and parents",
        "p_en": [
            "The Constitution, fundamental rights and duties",
            "Peaceful routes such as memorandums and public hearings",
            "Where to get legal aid"
        ]
    },
    {
        "n": "व्यावसायिक प्रशिक्षण",
        "w": "रोज़गार चाहने वाले युवा",
        "p": [
            "रोज़गार से जुड़े हुनर",
            "स्वरोज़गार की जानकारी",
            "सही करियर चुनने में मार्गदर्शन"
        ],
        "n_en": "Vocational training",
        "w_en": "Young people looking for work",
        "p_en": [
            "Job-related skills",
            "Information on self-employment",
            "Guidance in choosing the right career"
        ]
    }
];
const PEOPLE = [
    {
        "name": "शिवम मौर्य जी",
        "name_en": "Shivam Maurya Ji",
        "role": "संस्थापक, सम्राट योद्धा फाउंडेशन",
        "role_en": "Founder, सम्राट योद्धा फाउंडेशन",
        "about": "शिवम मौर्य जी ने सम्राट योद्धा फाउंडेशन की शुरुआत इस सोच के साथ की कि युवा की सबसे बड़ी ताकत उसका ज्ञान, उसकी योग्यता और उसका सफल भविष्य है। उनकी अगुवाई में फाउंडेशन गाँव-गाँव में पाठशालाएँ और कोचिंग सेंटर चलाकर बच्चों और युवाओं को शिक्षा, हुनर और संविधान की समझ से जोड़ रहा है।",
        "about_en": "Shivam Maurya Ji started सम्राट योद्धा फाउंडेशन with the belief that a young person’s greatest strength is their knowledge, their ability and a successful future. Under his leadership, the foundation runs pathshalas and coaching centres in villages, connecting children and young people with education, skills and an understanding of the Constitution.",
        "photo": "images/shivam.jpg"
    },
    {
        "name": "",
        "name_en": "",
        "role": "",
        "role_en": "",
        "about": "",
        "about_en": "",
        "photo": ""
    },
    {
        "name": "",
        "name_en": "",
        "role": "",
        "role_en": "",
        "about": "",
        "about_en": "",
        "photo": ""
    },
    {
        "name": "",
        "name_en": "",
        "role": "",
        "role_en": "",
        "about": "",
        "about_en": "",
        "photo": ""
    },
    {
        "name": "",
        "name_en": "",
        "role": "",
        "role_en": "",
        "about": "",
        "about_en": "",
        "photo": ""
    },
    {
        "name": "",
        "name_en": "",
        "role": "",
        "role_en": "",
        "about": "",
        "about_en": "",
        "photo": ""
    },
    {
        "name": "",
        "name_en": "",
        "role": "",
        "role_en": "",
        "about": "",
        "about_en": "",
        "photo": ""
    },
    {
        "name": "",
        "name_en": "",
        "role": "",
        "role_en": "",
        "about": "",
        "about_en": "",
        "photo": ""
    },
    {
        "name": "",
        "name_en": "",
        "role": "",
        "role_en": "",
        "about": "",
        "about_en": "",
        "photo": ""
    },
    {
        "name": "",
        "name_en": "",
        "role": "",
        "role_en": "",
        "about": "",
        "about_en": "",
        "photo": ""
    },
    {
        "name": "",
        "name_en": "",
        "role": "",
        "role_en": "",
        "about": "",
        "about_en": "",
        "photo": ""
    }
];
const SITE_EN = {
    "donate": "Donate",
    "nav.work": "Achievements",
    "nav.about": "About us",
    "nav.courses": "Courses",
    "nav.schools": "Pathshalas",
    "nav.join": "Join",
    "nav.contact": "Contact",
    "nav.gallery": "Gallery",
    "nav.blog": "Blog",
    "search.btn": "Learn about सम्राट योद्धा फाउंडेशन",
    "slogan": "Educate \u003Cb\u003E★\u003C/b\u003E Organise \u003Cb\u003E★\u003C/b\u003E Move forward",
    "hero.lead": "We run pathshalas village by village, bringing good education, skills and an understanding of the Constitution to children and young people.",
    "hero.find": "Find a coaching centre in your district",
    "join.cta": "Join सम्राट योद्धा फाउंडेशन",
    "stats.h2": "Our achievements so far",
    "stats.note": "In our village pathshalas, children and young people connect every day with education, skills and an understanding of the Constitution. Be part of this journey — \u003Ca href=\"#join\"\u003Ejoin us\u003C/a\u003E.",
    "about.h2": "Education first, then change in society",
    "about.lead": "सम्राट योद्धा फाउंडेशन works in education. Our goal is that every child and young person in every village gets good education, skills and the right guidance.",
    "about.p": "We believe a young person’s greatest strength is their knowledge, their ability and a successful future. First educate yourself and build your ability, then use that knowledge to take society forward.",
    "insp.h3": "Inspiration: Bharat Ratna Dr. Bhimrao Ambedkar",
    "insp.p": "Drawing inspiration from him means education, reason, an understanding of rights, constitutional paths, and constant effort for equality and justice.",
    "plan.h3": "Our 8-point action plan",
    "plan.1.t": "Education first",
    "plan.1.d": "Every effort begins with learning and ability.",
    "plan.2.t": "Competitive exam preparation",
    "plan.2.d": "Information on government, private-sector and other jobs.",
    "plan.3.t": "Skill development",
    "plan.3.d": "Computers, technical education, communication skills and vocational training.",
    "plan.4.t": "Legal awareness",
    "plan.4.d": "Constitutional rights, duties and ways of peaceful protest.",
    "plan.5.t": "Responsible leadership",
    "plan.5.d": "Not crowds, but a concrete plan for solutions.",
    "plan.6.t": "Peaceful democratic paths",
    "plan.6.d": "Memorandums, public hearings, dialogue and legal aid.",
    "plan.7.t": "Respect for young people’s time",
    "plan.7.d": "Every campaign should leave studies and exams unaffected.",
    "plan.8.t": "Review every effort",
    "plan.8.d": "What we achieved, what fell short, and how to do better.",
    "courses.h2": "Six courses: from early learning to employment",
    "courses.lead": "These courses run in each pathshala as needed. For admission details, message us on WhatsApp using the link under each course.",
    "courses.swipe": "Swipe sideways to see all 6 courses →",
    "gal.h2": "Glimpses",
    "gal.lead": "Some photos and videos from our pathshalas, programmes and campaigns.",
    "schools.h2": "Our pathshalas and coaching centres in your district",
    "f.state": "State",
    "f.district": "District",
    "blogt.h2": "Our blog",
    "blogt.all": "Read all posts →",
    "join.h2": "Join सम्राट योद्धा फाउंडेशन",
    "join.lead": "This work in education moves forward with companions like you. You can join in any of these ways:",
    "way.1.t": "Become a teacher",
    "way.1.d": "Teach children at your village pathshala.",
    "way.2.t": "Become a volunteer",
    "way.2.d": "Help with events, surveys and campaigns.",
    "way.3.t": "Start a pathshala in your village",
    "way.3.d": "Share details of a space and the children; we will do the rest together.",
    "way.4.t": "Become a member",
    "way.4.d": "Stay connected with the foundation’s activities.",
    "j.name": "Your name",
    "j.mobile": "Mobile number",
    "j.district": "District",
    "j.village": "Village",
    "j.how": "How would you like to join?",
    "j.opt1": "Become a teacher",
    "j.opt2": "Become a volunteer",
    "j.opt3": "Start a pathshala in my village",
    "j.opt4": "Become a member",
    "j.work": "What do you do?",
    "j.w0": "Choose",
    "j.w1": "Studying (student)",
    "j.w2": "Job",
    "j.w3": "Business",
    "j.w4": "Farming",
    "j.w5": "Homemaker",
    "j.w6": "Other",
    "j.submit": "Continue",
    "j.ready": "Your message is ready. The button below opens WhatsApp; tap Send there.",
    "j.send": "Send on WhatsApp",
    "don.h2": "Your support, a village child’s education",
    "don.lead": "Our pathshalas run with the support of people like you. Even something small, like a book, a notebook or a pen and pencil, helps a child’s education move forward.",
    "use.1": "Books, notebooks and study materials",
    "use.2": "Day-to-day running of pathshalas",
    "use.3": "Competitive exam preparation",
    "use.4": "Computer and skills training",
    "d.what": "What would you like to give the children?",
    "d.multi": "You can choose more than one.",
    "d.i1": "Books",
    "d.i2": "Notebooks",
    "d.i3": "Pens and pencils",
    "d.i4": "Stationery",
    "d.i5": "Others",
    "d.other": "What else would you like to give?",
    "d.qr.cap": "Scan this QR code with any UPI app, such as PhonePe, Google Pay, Paytm or BHIM, to donate.",
    "d.upi": "Donate with a UPI app",
    "d.btn": "Contact our team on WhatsApp",
    "d.contact": "Or contact us on Facebook / by email",
    "d.note": "The items you choose are written into the WhatsApp message automatically. Our team will talk to you and explain how to send them.",
    "contact.h2": "Contact us",
    "c.email": "Email",
    "foot.org": "सम्राट योद्धा फाउंडेशन",
    "foot.by": "Founder: Shivam Maurya Ji",
    "foot.tag": "Education is power.",
    "foot.web": "Website",
    "foot.join": "Get involved",
    "foot.copy": "सम्राट योद्धा फाउंडेशन",
    "search.close": "Close",
    "logo.alt": "सम्राट योद्धा फाउंडेशन logo",
    "nav.aria": "Main menu",
    "sl.label": "Highlights of सम्राट योद्धा फाउंडेशन",
    "logo.big.alt": "Round logo of सम्राट योद्धा फाउंडेशन: Ashoka pillar, book and students",
    "sl.prev": "Previous slide",
    "sl.next": "Next slide",
    "j.mobile.ph": "10-digit number",
    "d.other.ph": "e.g. school bags, drawing materials",
    "foot.aria": "Footer links",
    "search.title": "Search the website",
    "search.ph": "Search: pathshala, donation, courses, district…",
    "meta.desc": "सम्राट योद्धा फाउंडेशन runs pathshalas in villages, bringing education, skills and an understanding of the Constitution to children and young people.",
    "title": "सम्राट योद्धा फाउंडेशन | Education is Power",
    "founder.name": "Shivam Maurya Ji",
    "founder.av": "S",
    "founder.role": "Founder, सम्राट योद्धा फाउंडेशन"
};
