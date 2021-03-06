@ui @product
Feature: Viewing a product details

    Background:
        Given the site operates on a store in "Austria"
        And the store "Austria" is the default store

    Scenario: View product
        Given the site has a product "T-Shirt"
        And the product is active and published and available for store "Austria"
        When I open the product's detail page
        Then I should see the product name "T-Shirt"

