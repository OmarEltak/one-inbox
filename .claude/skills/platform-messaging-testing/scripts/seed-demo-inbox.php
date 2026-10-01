<?php
/**
 * Seed a local SQLite DB with a super-admin user (demo@ot1.test / password), a team, IG + FB +
 * WhatsApp pages and 6 conversations, so /inbox renders realistic data.
 *   php artisan tinker --execute="require '.claude/skills/platform-messaging-testing/scripts/seed-demo-inbox.php';"
 * Idempotent. LOCAL ONLY.
 */
use App\Models\{User, Team, ConnectedAccount, Page, Contact, Conversation, Message};
$u = User::firstOrCreate(['email' => 'demo@ot1.test'], ['name' => 'Demo Owner', 'password' => bcrypt('password'), 'email_verified_at' => now()]);
$u->forceFill(['is_super_admin' => true])->save();
$t = Team::firstOrCreate(['owner_id' => $u->id], ['name' => 'Demo Team', 'slug' => 'demo-team']);
$t->forceFill(['onboarding_completed_at' => now()])->save();
if (! $u->teams()->where('teams.id', $t->id)->exists()) $u->teams()->attach($t->id, ['role' => 'admin']);
$u->forceFill(['current_team_id' => $t->id])->save();
$acc = ConnectedAccount::firstOrCreate(['team_id' => $t->id, 'platform' => 'instagram', 'platform_user_id' => '178414000001'], ['name' => 'Acme IG', 'access_token' => 'x', 'is_active' => true, 'connected_at' => now()]);
$pages = [];
foreach ([['instagram','178414000001','Acme Store IG'],['facebook','105577575011406','Acme Store FB'],['whatsapp','201000000000','Acme WhatsApp']] as [$pl,$id,$nm]) {
  $pages[$pl] = Page::firstOrCreate(['team_id'=>$t->id,'platform'=>$pl,'platform_page_id'=>$id], ['connected_account_id'=>$acc->id,'name'=>$nm,'page_access_token'=>'x','is_active'=>true]);
}
$names = ['Sara Ahmed','Mohamed Ali','Lina Haddad','Omar Khaled','Nour Samir','Youssef Adel'];
foreach ($names as $i => $n) {
  $pl = array_keys($pages)[$i % 3];
  $c = Contact::firstOrCreate(['team_id'=>$t->id,'name'=>$n], ['lead_score'=> [85,40,65,20,95,55][$i], 'lead_status'=> ['hot','cold','warm','cold','hot','warm'][$i], 'first_seen_at'=>now()->subDays(3)]);
  $conv = Conversation::firstOrCreate(['page_id'=>$pages[$pl]->id,'platform_conversation_id'=>'conv-'.$i], ['team_id'=>$t->id,'platform'=>$pl,'contact_id'=>$c->id,'status'=>'open','last_message_at'=>now()->subMinutes($i*7),'last_message_preview'=>'Hi, is the leather bag still available in brown?','unread_count'=>$i%2 ? 0 : 2,'ai_paused'=>$i==1,'sales_stage'=>['new','interested','negotiating','ready_to_buy','new','closed_won'][$i] ?? 'new']);
  if ($conv->messages()->count() === 0) {
    $msgs = [
      ['inbound','contact','Hi, is the leather bag still available in brown?'],
      ['outbound','ai','Yes! The brown leather tote is in stock. Would you like the large or medium size?'],
      ['inbound','contact','Medium please. How much is delivery to Cairo?'],
      ['outbound','user','Delivery to Cairo is 60 EGP and takes 2-3 days.'],
      ['inbound','contact','Great, I will order today 🙏'],
    ];
    foreach ($msgs as $k => [$dir,$st,$txt]) {
      Message::create(['conversation_id'=>$conv->id,'direction'=>$dir,'sender_type'=>$st,'sender_id'=>$dir==='inbound'?$c->id:null,'content_type'=>'text','content'=>$txt,'platform_sent_at'=>now()->subMinutes(60-$k*5)]);
    }
  }
}
$t->clearActivePagesCache();
echo "seeded team {$t->id}, convs ".Conversation::where('team_id',$t->id)->count()."\n";
