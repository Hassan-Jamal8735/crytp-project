import random
import json

def generate_prices():
    return {
        'binance': round(random.uniform(50000, 50200), 2),
        'coinbase': round(random.uniform(50100, 50300), 2),
        'timestamp': '2023-03-15 14:00:00'
    }

# Call the function and print the result as JSON
if __name__ == "__main__":
    prices = generate_prices()
    print(json.dumps(prices))
