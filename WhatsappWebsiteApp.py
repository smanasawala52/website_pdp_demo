import traceback
from langchain_community.callbacks import get_openai_callback
from common import classify_query, generate_website_single_prompts, website_data, \
    website_generation_template_response_json, get_plan_info, parse_plan_info, plans_details, collect_unique_attributes, \
    pdp_faq, plan_faq, get_current_plan, get_plan_difference
from flask import Flask, request
from twilio.twiml.messaging_response import MessagingResponse
import json

app = Flask(__name__)

prompts = [
    {"text": "Create website for small real estate business"},
    {"text": "Show me templates for small businesses"},
    {"text": "How to add an online store to my website"},
    {"text": "Customize a website for my photography business"},
    {"text": "Create a professional logo with Wix"},
    {"text": "Show All Plans Core, Light and Business"},
    {"text": "Compare Plans Core, Light and Business"},
    {"text": "for account 12345 give plan details"},
    {"text": "for account 12345 compare plan with business"},
    {"text": "Create website"},
    {"text": "Show All Plans"},
    {"text": "Website FAQ"},
    {"text": "Plans FAQ"},
]
test_result_json_1 = {
    "title": "Choose Wix, the website builder for small business",
    "sub_title": [
        "Today, 85% of customers search online before they make a purchase – which means "
        "it’s never been more important for your small business to have a website.\n\n"
        "That’s why Vista has teamed up with Wix: To help small businesses design a website "
        "that gets noticed and remembered – for free. With 500+ customizable and eye-catching templates, "
        "free reliable web hosting, powerful SEO tools and 24/7 customer care, "
        "the Wix website builder is a complete online business solution.\n\n"
        "Sell your products with build-in ecommerce features, take bookings for your services and "
        "make it easy for customers to pay you online. Use the integrated marketing and customer management "
        "tools to help drive sales and conversations. And do it all with a professional look that "
        "complements your offline business branding.\n\nOver 180 million people worldwide have chosen "
        "Wix to design a website for free. And with this new partnership between Vista and Wix, you can do it, too."
    ],
    "image_url": [],
    "business_category": {
        "construction_and_real_estate": "business_images/Construction and Real Estate.jpg"
    }
}
test_result_json_2 = {
    "title": "Create a website you’re proud of",
    "sub_title": [
        "Discover the platform that gives you the freedom to make your own website - and develop your web presence "
        "exactly the way you want."
    ],
    "image_url": [
        "https://cms.cloudinary.vpsvc.com/image/upload/w_3780/f_auto/q_auto/v1647441697/digital/partners/wix/PDP-A"
        "/Hero_Img_2_4x_72dpi.png"
    ],
    "business_category": {
        "default": "https://cms.cloudinary.vpsvc.com/image/upload/w_3780/f_auto/q_auto/v1647441697/digital/partners"
                   "/wix/PDP-A/Hero_Img_2_4x_72dpi.png"
    }
}

test_result_json_3 = {
    "title": "Choose from 500+ professionally designed website templates",
    "sub_title": [
        "Each Wix business website template was created to meet unique small business needs. From automotive to the "
        "arts, photography to pets and everything in between, you’ll find customizable options designed with your "
        "industry in mind. Blog, Business, Online Store, Design, Photography and Education"
    ],
    "image_url": [
        "section_img.png"
    ],
    "business_category": {
        "default": "https://cms.cloudinary.vpsvc.com/image/upload/w_3780/f_auto/q_auto/v1647441697/digital/partners"
                   "/wix/PDP-A/Hero_Img_2_4x_72dpi.png"
    }
}
test_result_json_4 = {
    "title": "How to design a website for free",
    "sub_title": [
        "Follow these simple steps to build your own website today.\n\nSign up for a free website builder. Choose "
        "what kind of website you want to create.Customize a template or get a website made for you. Choose your "
        "starting point.Drag and drop 100s of design features. Add text, galleries, videos, vector art and more.Get "
        "ready for business. Add an online store, booking system or small business blog.Publish your site and go "
        "live. Start building your business's online presence.Drive traffic to your website. Use advanced SEO tools "
        "and integrated marketing solutions."
    ],
    "image_url": [
    ],
    "business_category": {
    }
}
test_result_json_5 = {
    "title": "Choose from 500+ professionally designed website templates",
    "sub_title": [
        "Each Wix business website template was created to meet unique small business needs. From automotive to the "
        "arts, photography to pets and everything in between, you’ll find customizable options designed with your "
        "industry in mind. Blog, Business, Online Store, Design, Photography and Education"
    ],
    "image_url": [
        "section_img.png"
    ],
    "business_category": {
        "arts_crafts_and_design": "business_images/Arts Crafts and Design.jpg"
    }
}
test_result_json_6 = {
    "title": "Make your own website with built-in tools to grow your small business online",
    "sub_title": [
        "Online Store\nStart selling online and grow your business with a powerful eCommerce platform.",
        "Online scheduling software\nLet clients book appointments, schedule services and pay online, all on your "
        "business website.",
        "Professional blog\nCreate a blog with built-in features to grow your reach and build a community.",
        "Logo maker\nDesign a one-of-a-kind professional logo to help build your business brand online.",
        "Custom domains\nGet a professional domain name that matches your small business and brand.",
        "SEO tools\nImprove your visibility on Google and other search engines with a full range of SEO features."
    ],
    "image_url": [
        "https://cms.cloudinary.vpsvc.com/image/upload/f_auto/q_auto/v1647445649/digital/partners/wix/PDP-A"
        "/cup_checkout_4x_72dpi.png",
        "https://cms.cloudinary.vpsvc.com/image/upload/f_auto/q_auto/v1647443753/digital/partners/wix/PDP-A"
        "/calendar_4x_72dpi.png",
        "https://cms.cloudinary.vpsvc.com/image/upload/f_auto/q_auto/v1647443867/digital/partners/wix/PDP-A"
        "/glasses_4x_72dpi.png",
        "https://cms.cloudinary.vpsvc.com/image/upload/f_auto/q_auto/v1647443954/digital/partners/wix/PDP-A"
        "/bottle_4_4x_72dpi.png",
        "https://cms.cloudinary.vpsvc.com/image/upload/f_auto/q_auto/v1647444041/digital/partners/wix/PDP-A"
        "/Homepage_4x_72dpi.png",
        "https://cms.cloudinary.vpsvc.com/image/upload/f_auto/q_auto/v1647444136/digital/partners/wix/PDP-A"
        "/road_trip_4x_72dpi.png"
    ],
    "business_category": {}
}


def display_compare_plan(plans):
    differences = get_plan_difference(plans)


def display_plan_details_full(plans):
    parsed_plans = [parse_plan_info(plan) for plan in plans]


def execute_search(search_query):
    if search_query:
        classify_category = classify_query(search_query)
        if classify_category is not None:
            category = classify_category['query_category']
            user_query = classify_category['user_query']
            business_category = classify_category.get('business_id', 'default')
            account_id = classify_category.get('account_id', None)
            if category == 'website_info' and user_query is not None:
                try:
                    with get_openai_callback() as cb:
                        generate_section_prompts_temp = generate_website_single_prompts()
                        result = generate_section_prompts_temp({
                            'text': website_data,
                            'user_query': user_query,
                            'business_category': business_category,
                            'response_json': json.dumps(website_generation_template_response_json),
                        })
                except Exception as e:
                    traceback.print_exception(type(e), e, e.__traceback__)
                else:
                    if isinstance(result, dict):
                        section1 = result.get("section", None).replace("json", "") \
                            .replace("```", "").replace("```", "")
                        # st.write(section1)
                        if section1 is not None:
                            section = json.loads(section1)
                            # st.json(section)
                            return create_website_info_section(section)
                    else:
                        # st.write(result)
                        return create_website_info_section(test_result_json_1)

            if category == 'compare_plans':
                compare_plans = classify_category.get('compare_plans', None)
                if account_id is not None:
                    account_details = get_current_plan(account_id)
                    if account_details is not None:
                        account_current_plan = account_details["current_plan"]
                        account_current_plan_credit = account_details["credit"]
                        # st.header(f"Current Plan: {account_current_plan}")
                        # st.header(f"Credit Available: {account_current_plan_credit}")

                        if compare_plans is not None and account_current_plan is not None:
                            compare_plans.append(account_current_plan)
                            plans = get_plan_info(compare_plans)
                            display_compare_plan(plans)
                        else:
                            plans = get_plan_info(compare_plans)
                            display_compare_plan(plans)
                    else:
                        plans = get_plan_info(compare_plans)
                        display_compare_plan(plans)
                else:
                    plans = get_plan_info(compare_plans)
                    display_compare_plan(plans)
            if category == 'plan_info':
                compare_plans = classify_category.get('compare_plans', None)
                if account_id is not None:
                    account_details = get_current_plan(account_id)
                    if account_details is not None:
                        account_current_plan = account_details["current_plan"]
                        account_current_plan_credit = account_details["credit"]
                        # st.header(f"Current Plan: {account_current_plan}")
                        # st.header(f"Credit Available: {account_current_plan_credit}")
                        # st.write(account_current_plan)
                        if compare_plans is not None and account_current_plan is not None:
                            compare_plans.append(account_current_plan)
                            plans = get_plan_info(compare_plans)
                            display_compare_plan(plans)
                        else:
                            plans = get_plan_info([account_current_plan])
                            display_plan_details_full(plans)
                    else:
                        plans = get_plan_info(classify_category.get('plan_id', None))
                        display_plan_details_full(plans)
                else:
                    plans = get_plan_info(classify_category.get('plan_id', None))
                    display_plan_details_full(plans)
        if category == 'current_plan':
            if account_id is not None:
                # get current Plan information
                # 1) get account information from account details
                account_details = get_current_plan(account_id)
                account_current_plan = account_details["current_plan"]
                account_current_plan_credit = account_details["credit"]
                # st.header(f"Current Plan: {account_current_plan}")
                # st.header(f"Credit Available: {account_current_plan_credit}")
                plans = get_plan_info([account_current_plan])
                display_plan_details_full(plans)
        if category == 'website_faq':
            str_website_faq = ''
            for faq in pdp_faq:
                str_website_faq += f"**{faq['q']}**: {faq['a']}\n"
        if category == 'plan_faq':
            str_plan_faq = ''
            for faq in plan_faq:
                str_plan_faq += f"**{faq['q']}**: {faq['a']}\n"


def display_plan_details(plan_info, showLables=False):
    table_data = []
    for feature_category, features in [
        ("Site Features", plan_info["site_features"]),
    ]:
        if showLables:
            row = [f"**{feature_category}**"]
        else:
            row = ['']
        table_data.append(row)
        for item in enumerate(features):
            item_temp = list(item)[1]
            for key, value in item_temp.items():
                if showLables:
                    row = [key]
                else:
                    row = [value]
                table_data.append(row)
    # st.table(table_data)


# Define sets of colors and fonts
background_colors = ['#FF6347', '#4682B4', '#7FFF00', '#FFD700', '#8A2BE2']
fonts = ['Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Verdana']


def create_website_info_section(result_json):
    print(result_json)
    str = ''
    if result_json is not None:
        str += f"**{result_json['title']}**"
        str += f"{result_json['sub_title'][0]}"
        if result_json['business_category'] is not None and len(result_json['business_category']) > 0:
            image_data = result_json['business_category']
            first_key = next(iter(image_data))
            image_path = image_data[first_key]
            str += f"{image_path}"
        elif result_json['image_url'] is not None and len(result_json['image_url']) > 0:
            result_json['image_url'][0]
            str += f"{result_json['image_url'][0]}"
    return str


@app.route("/whatsapp", methods=['POST'])
def whatsapp_reply():
    incoming_msg = request.form.get('Body', '').strip().lower()
    print(f"Incoming Message: {incoming_msg}")
    incoming_msg = request.values.get('Body', '').strip().lower()
    print(incoming_msg)
    response = MessagingResponse()
    msg = response.message()

    try:
        reply = execute_search(incoming_msg)
    except Exception as e:
        reply = str(e)

    msg.body(reply[:1000])
    return str(response)


if __name__ == "__main__":
    app.run(port=5000, host='localhost', debug=True)