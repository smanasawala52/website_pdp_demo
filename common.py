# importing necessary packages from langchain
import json
import os
import openai

from dotenv import load_dotenv
from langchain.chains import LLMChain
from langchain.chat_models import ChatOpenAI
from langchain.prompts import PromptTemplate

load_dotenv()
OPENAI_API_KEY = os.getenv('OPENAI_API_KEY')
llm = ChatOpenAI(openai_api_key=OPENAI_API_KEY, model_name="gpt-4o", temperature=0.7)
openai.api_key = OPENAI_API_KEY


def load_data(filename):
    try:
        with open(filename, 'r') as f:
            return json.load(f)
    except FileNotFoundError:
        return []


website_data = load_data('json/data.json')
pdp_faq = load_data('json/pdp_faq.json')
plans_details = load_data('json/plan.json')
plan_faq = load_data('json/plan_faq.json')
account_details = load_data('json/accounts.json')
website_generation_template_response_json = load_data('json/website_generation_template_response_json.json')


def classify_query(query):
    response_json = {
        "query_category": "category_1",
        "user_query": "place_1",
        "business_id": "business_id_1",
        "account_id": "account_id_1",
        "current_plan": "current_plan_id_1",
        "monthly_or_yearly": "monthly or yearly",
        "compare_plans": ["compare_plan_1", "compare_plan_2"],
        "plan_id": ["plan_1", "plan_2"]
    }
    response = openai.chat.completions.create(
        model="gpt-3.5-turbo",
        messages=[
            {"role": "system",
             "content": "You are an assistant that classifies queries into one of the following query categories: "
                        "plan_info, website_info, website_faq, plan_faq, upgrade_plan. "
                        "If and only if the user specifies any business, here is the list of "
                        "business_id: agriculture_and_farming, animal_and_pet_care, arts_crafts_and_design, "
                        "automotive_and_transportation, construction_and_real_estate, education_services, "
                        "entertainment_and_recreation, finance_and_insurance, household_services, "
                        "non_profits_charity_and_politics, professional_services, technology. "
                        "Also, if and only if the user specifies a plan "
                        "(from Free, Light, Core, Business, and Business Elite) "
                        "to get more information, then capture plan_ids. "
                        "Also, if and only if the user specifies an account id, then capture account_id. "
                        "Also, if and only if the user specifies the current plan "
                        "(from Free, Light, Core, Business, and Business Elite), then capture current_plan, "
                        "and if they specify if the current plan is monthly or yearly, then capture that as well. "
                        "Also, if and only if the user wants to compare a couple of plans "
                        "(from Free, Light, Core, Business, and Business Elite), then capture compare_plans."
                        "Also, if and only if the user wants to compare a its current plan with "
                        "(from Free, Light, Core, Business, and Business Elite), then capture compare_plans."
             },
            {"role": "user",
             "content": f"Please take a deep breath and extract below information from Query: {query}\n"
                        f"query_category, user_query, business_id, account_id, current_plan, "
                        f"monthly_or_yearly, compare_plans and plan_id in json in lower case {response_json}"}
        ]
    )
    category = response.choices[0].message.content.strip().replace('\'', '\"')
    category = json.loads(category)
    return category


def compare_plan_diff(query):
    response_json = {
        "query_category": "category_1",
        "user_query": "place_1",
        "business_id": "business_id_1",
        "account_id": "account_id_1",
        "current_plan": "current_plan_id_1",
        "monthly_or_yearly": "monthly or yearly",
        "compare_plans": ["compare_plan_1", "compare_plan_2"],
        "plan_id": ["plan_1", "plan_2"]
    }
    response = openai.chat.completions.create(
        model="gpt-3.5-turbo",
        messages=[
            {"role": "system",
             "content": "You are an assistant that classifies extracts difference from plans"
                        "(from Free, Light, Core, Business, and Business Elite), then capture compare_plans."
             },
            {"role": "user",
             "content": f"Please take a deep breath and extract below information from Query: {query}\n"
                        f"query_category, user_query, business_id, account_id, current_plan, "
                        f"monthly_or_yearly, compare_plans and plan_id in json in lower case {response_json}"}
        ]
    )
    category = response.choices[0].message.content.strip().replace('\'', '\"')
    category = json.loads(category)
    return category


def generate_website_single_prompts():
    website_generation_template = """
    Take a deep breath and work on this step by step. Return result in JSON only, no additional warpper text.
    You are an expert website customer server.
    section details: {text}
    You are an expert website customer server. Given the above sections details, it is your job to fetch the section,\
    based on '{user_query}' and business_category: {business_category}. \
    Most importantly, make use of title and sub_title attribute to make sure section is fetched correctly. \
    Make sure to format your response like JSON below and use it as a guide. \
    {response_json}
    """
    website_generation_prompt = PromptTemplate(
        input_variables=["text", "user_query", "business_category", "response_json"],
        template=website_generation_template
    )
    quiz_generation_chain = LLMChain(llm=llm, prompt=website_generation_prompt, output_key="section", verbose=True)
    return quiz_generation_chain


def get_plan_info(plan_titles=None):
    matched_plans = []
    if plan_titles is not None and len(plan_titles)>0:
        for plan_title in plan_titles:
            for plan_temp in plans_details:
                if plan_temp["title"].lower() == plan_title.lower():
                    matched_plans.append(plan_temp)
    else:
        matched_plans = plans_details

    if matched_plans is None:
        matched_plans = plans_details

    if matched_plans is []:
        matched_plans = plans_details
    return matched_plans


def parse_plan_info(plan_data):
    extracted_info = {
        "title": plan_data["title"],
        "sub_title": plan_data["sub_title"],
        "sub_title_tool_tip": plan_data["sub_title_tool_tip"],
        "price_monthly": plan_data["price_monthly"],
        "price_yearly": plan_data["price_yearly"],
        "price_sub_title": plan_data["price_sub_title"],
        "basic_attributes": plan_data["basic_attributes"],
    }
    return extracted_info


# Function to collect unique attributes
def collect_unique_attributes(plans, unique_attributes=None):
    for key_plan, value_plan in plans.items():
        if isinstance(value_plan, list):
            for item in value_plan:
                for k, v in item.items():
                    unique_attributes.add(k)
        elif isinstance(value_plan, dict):
            for k, v in value_plan.items():
                unique_attributes.add(k)


def get_current_plan(account_id):
    for item in account_details:
        if item["account_id"] == account_id:
            return item
    return None


def get_plan_difference(plans):
    if plans is None:
        plans = plans_details
    if plans is []:
        plans = plans_details
        # Initialize dictionaries to store the differences
    site_features_diff = {"label": []}

    # Iterate through each plan and compare attributes
    for plan in plans:
        title = plan['title']
        site_features = plan['site_features']
        payment_tools = plan['payment_tools']

        # Compare site features
        if title not in site_features_diff:
            site_features_diff[title] = []

        for feature_category, features in [
            ("Site Features", plan["site_features"]),
            ("Payment Tools", plan["payment_tools"]),
            ("Complete Ecommerce", plan["complete_ecommerce_platform"]),
            ("Bookings Platform", plan["bookings_platform"]),
        ]:
            for item in enumerate(features):
                item_temp = list(item)[1]
                for key, value in item_temp.items():
                    # st.write(key + ' :: ' + value)
                    site_features_diff[title].append(value)
                    site_features_diff["label"].append(key)

    # Extracting differing values
    differences = []
    data = site_features_diff
    plans_title = [plan for plan in data if plan != "label"]
    for i in range(len(data[plans_title[0]])):
        plan_values = [data[plan][i] for plan in plans_title]
        if len(set(plan_values)) > 1:
            difference = {"label": data["label"][i]}
            for plan, value in zip(plans_title, plan_values):
                difference[plan] = value
            differences.append(difference)

