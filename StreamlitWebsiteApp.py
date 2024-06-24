import traceback
import numpy as np
import json

import streamlit as st
from langchain_community.callbacks import get_openai_callback
from st_clickable_images import clickable_images

from common import classify_query, generate_website_single_prompts, website_data, \
    website_generation_template_response_json, get_plan_info, parse_plan_info, plans_details, collect_unique_attributes, \
    pdp_faq, plan_faq, get_current_plan, get_plan_difference, get_prompt_images
import random

st.set_page_config(
    page_title="Website PDP Assistant",
    page_icon="🧊",
    layout="wide",
    initial_sidebar_state="expanded",
    menu_items={
    }
)
if 'is_visible' not in st.session_state:
    st.session_state.is_visible = False  # Set initial visibility to True

st.markdown(
    """
<style>
stButton, .button {
    width: 200px;
    height: 200px;
    padding-top: 0px !important;
    padding-bottom: 0px !important;
}
</style>
""",
    unsafe_allow_html=True,
)

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
    {"text": "Get started with Wix for free"},
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


# Function to create a grid layout
def create_grid(places, columns=6, show_checkbox=True):
    rows = len(places) // columns + int(len(places) % columns > 0)
    selected_places = []
    for row in range(rows):
        cols = st.columns(columns)
        for col_idx, place_idx in enumerate(range(row * columns, min((row + 1) * columns, len(places)))):
            with cols[col_idx]:
                place = places[place_idx]
                # Display the image with a checkbox
                if st.button(place["text"]):
                    selected_places.append(place["text"])
    return selected_places


hide_img_fs = '''
<style>
button[title="View fullscreen"]{
    visibility: hidden;}
</style>
'''

st.markdown(hide_img_fs, unsafe_allow_html=True)


# Function to create a grid layout
def create_grid_image(images, columns=6, show_checkbox=True):
    rows = len(images) // columns + int(len(images) % columns > 0)
    selected_places = []
    for row in range(rows):
        cols = st.columns(columns)
        for col_idx, place_idx in enumerate(range(row * columns, min((row + 1) * columns, len(images)))):
            with cols[col_idx]:
                st.image(images[place_idx])


# Function to generate a random color in hex format
def random_color():
    return "#{:06x}".format(random.randint(0, 0xFFFFFF))


def generate_home_page():
    search_query = st.text_input("Website PDP Assistant:")
    # create_grid_image(get_prompt_images())
    # search_query_btn = create_grid(prompts)
    # if search_query_btn is not None and len(search_query_btn) > 0:
    # search_query = search_query_btn[0]
    execute_search(search_query)


def display_plan_details_full(plans):
    if plans is None:
        plans = plans_details
    if plans is []:
        plans = plans_details
    st.session_state.is_visible = False

    # st.json(plans)
    parsed_plans = [parse_plan_info(plan) for plan in plans]
    # st.write(parsed_plans)
    cols_header = st.columns([1 for _ in range(len(parsed_plans) + 1)])
    with cols_header[0]:
        display_plan_header(parsed_plans[0], True)
    for idx, plan_info in enumerate(parsed_plans):
        with cols_header[idx + 1]:
            display_plan_header(plan_info)

    # show_hide_button = st.button("Show All Features")
    # if show_hide_button:
    # st.session_state.is_visible = not st.session_state.is_visible
    # Display details for each parsed plan
    # if st.session_state.is_visible:
    cols = st.columns([1 for _ in range(len(plans) + 1)])
    with cols[0]:
        display_plan_details(plans[0], True)
    for idx, plan_info in enumerate(plans):
        with cols[idx + 1]:
            display_plan_details(plan_info)


def display_compare_plan(plans):
    st.table(get_plan_difference(plans))


def execute_search(search_query):
    st.session_state.is_visible = False
    if search_query:
        st.subheader(f"Prompt Entered by User: {search_query}")
        classify_category = classify_query(search_query)
        # st.write(classify_category)
        st.divider()
        if classify_category is not None:
            category = classify_category['query_category']
            user_query = classify_category['user_query']
            business_category = classify_category.get('business_id', 'default')
            account_id = classify_category.get('account_id', None)
            if category == 'website_info' and user_query is not None:
                with st.spinner('loading.....'):
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
                        st.error("Error")
                    else:
                        if isinstance(result, dict):
                            section1 = result.get("section", None).replace("json", "") \
                                .replace("```", "").replace("```", "")
                            # st.write(section1)
                            if section1 is not None:
                                section = json.loads(section1)
                                # st.json(section)
                                create_website_info_section(section)
                        else:
                            # st.write(result)
                            create_website_info_section(test_result_json_1)
            if category == 'compare_plans':
                compare_plans = classify_category.get('compare_plans', None)
                if account_id is not None:
                    account_details = get_current_plan(account_id)
                    if account_details is not None:
                        account_current_plan = account_details["current_plan"]
                        account_current_plan_credit = account_details["credit"]
                        st.header(f"Current Plan: {account_current_plan}")
                        st.header(f"Credit Available: {account_current_plan_credit}")

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
                        st.header(f"Current Plan: {account_current_plan}")
                        st.header(f"Credit Available: {account_current_plan_credit}")
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
                st.header(f"Current Plan: {account_current_plan}")
                st.header(f"Credit Available: {account_current_plan_credit}")
                plans = get_plan_info([account_current_plan])
                display_plan_details_full(plans)
        if category == 'website_faq':
            for faq in pdp_faq:
                with st.expander(faq['q']):
                    st.write(faq['a'])
        if category == 'plan_faq' or category == 'plans_faq':
            for faq in plan_faq:
                with st.expander(faq['q']):
                    st.write(faq['a'])


def display_plan_header(plan_info, showLables=False):
    if not showLables:
        st.subheader(f"{plan_info['title']} Plan")
        st.write(f"{plan_info['sub_title']}")
        st.write(f"{plan_info['sub_title']}")
        st.write(f"{plan_info['price_monthly']}/month")
        st.write(f"{plan_info['price_yearly']} billed yearly")
        s = ''
        for i in plan_info['basic_attributes']:
            s += "- " + i + "\n"
        st.markdown(s)


def display_plan_details(plan_info, showLables=False):
    table_data = []
    for feature_category, features in [
        ("Site Features", plan_info["site_features"]),
        ("Payment Tools", plan_info["payment_tools"]),
        ("Complete Ecommerce", plan_info["complete_ecommerce_platform"]),
        ("Bookings Platform", plan_info["bookings_platform"]),
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
    st.table(table_data)


# Define sets of colors and fonts
background_colors = ['#FF6347', '#4682B4', '#7FFF00', '#FFD700', '#8A2BE2']
fonts = ['Arial', 'Helvetica', 'Times New Roman', 'Courier New', 'Verdana']


def print_title_subheader(result_json=None):
    st.subheader(result_json['title'])
    if isinstance(result_json['sub_title'], str):
        st.write(result_json['sub_title'])
    else:
        if result_json['sub_title'] is not None and len(result_json['sub_title']) > 1:
            st.write(result_json['sub_title'][0])
        elif result_json['sub_title'] is not None and len(result_json['sub_title']) > 0:
            st.write(result_json['sub_title'][0])
    cols_button = st.columns(2)
    with cols_button[0]:
        st.button("Get Started")
    with cols_button[1]:
        st.button("See All Premium Plans")
    st.caption(website_data["terms_condition"])


def create_website_info_section(result_json):
    # Randomly choose background color and font
    chosen_color = random.choice(background_colors)
    chosen_font = random.choice(fonts)
    st.markdown("""
        <style>
            .stDivider {margin-top: 0px; margin-bottom: 0px;}
            .stTabs [data-baseweb="tab-list"] button {padding: 0px 0px;}
            .stTabs [data-baseweb="tab"] {margin-right: 0px;}
            .stHeader {margin-bottom: 0px;}
            .stSubheader {margin-bottom: 0px;}
            .stMarkdown {margin-bottom: 0px;}
            h3 {padding-bottom: 0px; padding-top: 0px;}
            p {padding-bottom: 0px; padding-top: 0px; margin-bottom: 0px}
            body {{
                background-color: {chosen_color};
                font-family: '{chosen_font}', sans-serif;
            }}
        </style>
    """, unsafe_allow_html=True)
    print(result_json)
    if result_json is not None:
        if result_json['business_category'] is not None and len(result_json['business_category']) > 0:
            cols1 = st.columns(2)
            with cols1[0]:
                print_title_subheader(result_json)
            with cols1[1]:
                image_data = result_json['business_category']
                first_key = next(iter(image_data))
                image_path = image_data[first_key]
                st.image(image_path)
        elif result_json['image_url'] is not None and len(result_json['image_url']) > 0:
            cols2 = st.columns(2)
            with cols2[0]:
                print_title_subheader(result_json)
            with cols2[1]:
                if len(result_json['image_url']) > 1:
                    create_grid_image(result_json['image_url'], columns=3)
                else:
                    st.image(result_json['image_url'][0])
        else:
            print_title_subheader(result_json)



generate_home_page()
# create_website_info_section(test_result_json_1)
# create_website_info_section(test_result_json_2)
# create_website_info_section(test_result_json_3)
# create_website_info_section(test_result_json_4)
# create_website_info_section(test_result_json_5)
# create_website_info_section(test_result_json_6)
