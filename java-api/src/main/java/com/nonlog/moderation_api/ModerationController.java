package com.nonlog.moderation_api;

import org.springframework.web.bind.annotation.*;
import java.util.*;


// このクラスがAPIのリクエストを受け付ける窓口ですよ宣言
@RestController
public class ModerationController {
    // NGワードのリスト
    // 勉強用のためサンプルです。
    // List<String>の<>はジェネリクス（総称型）。データ型をあとから自由に指定・固定できる仕組み。
    private static final List<String> NG_WORDS = List.of(
        "死ね", "殺す", "バカ", "アホ"
    );

    // POST /api/check-message にリクエストが来たときに、このメソッドが呼ばれる
    @PostMapping("/api/check-message")
    // @RequestBody Map<String, String> body→リクエストで送られてきたJSONを自動的にJavaのMapという方に変換してくれる。
    public Map<String, Object> checkMessage(@RequestBody Map<String, String> body) {
        String message = body.get("message");

        // メッセージが送られてこなかった場合の保険
        if(message == null){
            message = "";
        }

        // NGワードが1つでも含まれていたら不適切と判定
        boolean isValid = NG_WORDS.stream().noneMatch(message::contains);

        // new HashMap<>の<>は右側の中身を省略。左辺の方指定を推論して自動的に補ってくれる
        Map<String, Object> result = new HashMap<>();
        result.put("isValid", isValid);
        if(!isValid){
            result.put("reason", "不適切な表現が含まれています");
        }

        // 戻り値がMapのため、自動的にJSON形式に変換してレスポンスとして返してくれる
        return result;
    }

}
