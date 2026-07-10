# v1.7.1追加：ビルド用ステージ：Mavenでjarファイルを作る
FROM maven:3.9-eclipse-temurin-25 AS build
WORKDIR /app
COPY pom.xml .
COPY src ./src
RUN mvn clean package -DskipTests

# v1.7.1追加：実行用ステージ：軽量なJava実行環境だけを使う
FROM eclipse-temurin:25-jre
WORKDIR /app
COPY --from=build /app/target/*.jar app.jar
EXPOSE 8081
ENTRYPOINT ["java", "-jar", "app.jar"]
