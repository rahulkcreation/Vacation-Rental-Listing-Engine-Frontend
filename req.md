sun list-view.php mein bs ek change karna hai like agar url mein kuchh parameters ho to uske according filter ho jana chahiye iske list agar nhi hai to sare jaise dikh rhe hai wese show honge bs latest entry pehle show honge.

sb sun parameters like aese ho like ?type=appartment&location=jaipur&guests=2&amenities=wifi,pool,parking&sort=price_low_to_high, min-price=1000&max-price=5000

to uske according filter ho jana chahiye like agar location jaipur hai to jaipur ke hi show honge agar guests 2 hai to 2 guests wale hi show honge agar amenities wifi,pool,parking hai to wifi,pool,parking wale hi show honge agar sort price_low_to_high hai to price low to high wale hi show honge. or agar koi parameter nhi hai to sare jaise dikh rhe hai wese show honge bs latest entry pehle show honge.

1. sun location ke liye parameter lega and `wp_ls_location` mein ja kar `name` column dekhega match karega and agar nhi mil rha to uske jaise koii similar lega aur `wp_ls_listings` mein `address` column dekhega agar match hua to thik show kar dega agar location, address dono mein wo value nhi mila to similar word wala list uthaega and show kar dega.

2. ab sun type hua to uske liye parameter lega and `wp_ls_types` table mein jaega match karega and then phir `wp_ls_listings` mein `type` column mein id match karega and wo list show karega.

3. price ke liye wahi `wp_ls_listings` mein `price` column dekhega uske according list show karega.

4. ab sun amenities hua to uske liye parameter lega and `wp_ls_amenities` table mein jaega match karega name in name column and then phir `wp_ls_listings` mein `amenities` column mein id match karega and wo list show karega.

